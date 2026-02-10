<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\SyncController;
use Illuminate\Console\Command;

class SyncRegistryData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:registry {--all : Sync all data} {--offices : Sync only offices} {--employees : Sync only employees} {--users : Sync only users}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Sync data from pbo-registry to local database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $controller = new SyncController();
        
        if ($this->option('all') || (!$this->option('offices') && !$this->option('employees') && !$this->option('users'))) {
            // Sync all
            $this->info('Syncing offices...');
            $response = $controller->syncOffices();
            $data = json_decode($response->getContent());
            $this->info('✓ Offices: ' . $data->synced . ' synced');

            $this->info('Syncing employees...');
            $response = $controller->syncEmployees();
            $data = json_decode($response->getContent());
            $this->info('✓ Employees: ' . $data->synced . ' synced');

            $this->info('Syncing users...');
            $response = $controller->syncUsers();
            $data = json_decode($response->getContent());
            $this->info('✓ Users: ' . $data->synced . ' synced');

            $this->info('All data synced successfully!');
        } else {
            if ($this->option('offices')) {
                $this->info('Syncing offices...');
                $response = $controller->syncOffices();
                $data = json_decode($response->getContent());
                $this->info('✓ Offices: ' . $data->synced . ' synced');
            }

            if ($this->option('employees')) {
                $this->info('Syncing employees...');
                $response = $controller->syncEmployees();
                $data = json_decode($response->getContent());
                $this->info('✓ Employees: ' . $data->synced . ' synced');
            }

            if ($this->option('users')) {
                $this->info('Syncing users...');
                $response = $controller->syncUsers();
                $data = json_decode($response->getContent());
                $this->info('✓ Users: ' . $data->synced . ' synced');
            }
        }

        return Command::SUCCESS;
    }
}
