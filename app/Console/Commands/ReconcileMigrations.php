<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * For a database whose migrations table fell out of step (e.g. restored from a copy): finds pending migrations whose
 * changes are already in the database and, with --apply, records them as run, so `php artisan migrate` runs only the
 * ones that are really new. Nothing in a migration is executed: each pending migration is dry-run (pretend mode) and
 * every statement it would run is checked against the database. Only these can be checked: create table, add column,
 * add foreign key, add index. A migration with anything else (data, column changes, drops) is left for a person.
 */
class ReconcileMigrations extends Command
{
    protected $signature = 'migrate:reconcile {--apply : Record the migrations already in the database as run}';

    protected $description = 'Find pending migrations whose changes are already in the database (and record them as run with --apply)';

    public function handle(Migrator $migrator): int
    {
        $files = $migrator->getMigrationFiles(array_merge([database_path('migrations')], $migrator->paths()));
        $ran = $migrator->getRepository()->getRan();
        $pending = array_diff_key($files, array_flip($ran));

        if (!$pending) {
            $this->info('No pending migrations: the migrations table matches the files.');

            return self::SUCCESS;
        }

        $rows = [];
        $present = [];
        foreach ($pending as $name => $path) {
            [$status, $detail] = $this->check($path);
            $rows[] = [$name, $status, $detail];
            if ($status === 'already in the database') {
                $present[] = $name;
            }
        }
        $this->table(['Pending migration', 'Finding', 'Why'], $rows);

        $toRun = count($pending) - count($present);
        if (!$present) {
            $this->info("Nothing to record: run `php artisan migrate` for the {$toRun} pending migration(s).");

            return self::SUCCESS;
        }

        if (!$this->option('apply')) {
            $this->warn(count($present) . ' migration(s) are already in the database. Run `php artisan migrate:reconcile --apply` to record them as run'
                . ($toRun ? ", then `php artisan migrate` for the other {$toRun}." : '.'));

            return self::SUCCESS;
        }

        $repository = $migrator->getRepository();
        $batch = $repository->getNextBatchNumber();
        foreach ($present as $name) {
            $repository->log($name, $batch);
        }
        $this->info('Recorded ' . count($present) . " migration(s) as run (batch {$batch})." . ($toRun ? " Now run `php artisan migrate` for the other {$toRun}." : ''));

        return self::SUCCESS;
    }

    /** @return array{0: string, 1: string} finding and reason */
    private function check(string $path): array
    {
        try {
            $migration = require $path;
            if (!is_object($migration) || !method_exists($migration, 'up')) {
                return ['needs a person', 'not an anonymous migration class'];
            }
            $connection = DB::connection($migration->getConnection() ?? null);
            $statements = collect($connection->pretend(fn () => $migration->up()))
                ->pluck('query')
                // Lookups the migration makes (hasTable, hasColumn...) aren't changes
                ->reject(fn ($sql) => preg_match('/^\s*(select|pragma|show)\b/i', $sql))
                ->values();
        } catch (\Throwable $e) {
            return ['needs a person', 'could not dry-run it: ' . $e->getMessage()];
        }

        if ($statements->isEmpty()) {
            return ['needs a person', 'it makes no schema changes that can be checked'];
        }

        $missing = [];
        foreach ($statements as $sql) {
            $found = $this->statementApplied($sql);
            if ($found === null) {
                return ['needs a person', 'cannot check: ' . mb_strimwidth(preg_replace('/\s+/', ' ', $sql), 0, 90, '…')];
            }
            if ($found !== true) {
                $missing[] = $found;
            }
        }

        return $missing
            ? ['needs to run', 'missing: ' . implode(', ', array_slice($missing, 0, 3)) . (count($missing) > 3 ? '…' : '')]
            : ['already in the database', $statements->count() . ' change(s), all present'];
    }

    /**
     * true when the statement's change is in the database, a description of what's missing when it isn't, null when
     * this kind of statement can't be checked.
     */
    private function statementApplied(string $sql): bool|string|null
    {
        $q = '[`"\[]?';
        $qe = '[`"\]]?';
        $sql = trim(preg_replace('/\s+/', ' ', $sql));

        if (preg_match("/^create table (?:if not exists )?{$q}(\w+){$qe}/i", $sql, $m)) {
            return Schema::hasTable($m[1]) ?: "table {$m[1]}";
        }
        if (preg_match("/^alter table {$q}(\w+){$qe} add constraint {$q}(\w+){$qe} foreign key/i", $sql, $m)) {
            $names = array_map('strtolower', array_column(Schema::getForeignKeys($m[1]), 'name'));

            return in_array(strtolower($m[2]), $names, true) ?: "foreign key {$m[2]}";
        }
        if (preg_match("/^alter table {$q}(\w+){$qe} add (?:unique |index |fulltext |spatial index )(?:index )?{$q}(\w+){$qe}/i", $sql, $m)
            || preg_match("/^create (?:unique )?index {$q}(\w+){$qe} on {$q}(\w+){$qe}/i", $sql, $c) && ($m = [null, $c[2], $c[1]])) {
            $names = array_map('strtolower', array_column(Schema::getIndexes($m[1]), 'name'));

            return in_array(strtolower($m[2]), $names, true) ?: "index {$m[2]}";
        }
        if (preg_match("/^alter table {$q}(\w+){$qe} add (?:column )?{$q}(\w+){$qe} /i", $sql, $m)
            && !preg_match('/^(constraint|primary|unique|index|fulltext|spatial|foreign)$/i', $m[2])) {
            return Schema::hasColumn($m[1], $m[2]) ?: "column {$m[1]}.{$m[2]}";
        }

        return null;
    }
}
