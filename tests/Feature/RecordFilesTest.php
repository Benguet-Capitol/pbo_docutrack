<?php

namespace Tests\Feature;

use App\Models\Record;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Records uploads: private storage, opened only through the permission-checked endpoints */
class RecordFilesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Record::FILE_DISK);
        Storage::fake('public');
    }

    private function upload(string $type, string $roleOfUploader = 'Administrator'): Record
    {
        $this->actingAs($this->makeUser($roleOfUploader))->post('/api/records', [
            'record_no' => 'ADM-2026-10-' . random_int(100, 999),
            'record_type' => $type,
            'title' => 'Office order',
            'file' => UploadedFile::fake()->create('order.pdf', 20, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertCreated();

        return Record::latest('id')->first();
    }

    public function test_uploads_are_saved_in_private_storage_not_the_public_disk(): void
    {
        $record = $this->upload('Provincial Budget');

        Storage::disk(Record::FILE_DISK)->assertExists($record->image_path);
        Storage::disk('public')->assertMissing($record->image_path);
        $this->assertStringStartsWith('records/', $record->image_path);
    }

    public function test_a_signed_in_user_with_records_view_can_open_and_download_a_file(): void
    {
        $record = $this->upload('Provincial Budget');
        $reviewer = $this->makeUser('Reviewer');

        $this->actingAs($reviewer)->get("/api/records/{$record->id}/view")->assertOk();
        $this->actingAs($reviewer)->get("/api/records/{$record->id}/download")->assertOk()->assertDownload();
    }

    public function test_guests_can_not_open_a_file(): void
    {
        $record = $this->upload('Provincial Budget');
        auth()->logout();

        $this->get("/api/records/{$record->id}/view")->assertRedirect(route('login'));
    }

    public function test_a_role_without_records_view_can_not_open_a_file(): void
    {
        $record = $this->upload('Provincial Budget');
        $this->makeRole('No Records', ['documents.view']);

        $this->actingAs($this->makeUser('No Records'))->getJson("/api/records/{$record->id}/view")->assertForbidden();
    }

    public function test_administrative_files_are_only_for_those_allowed_to_see_them(): void
    {
        $record = $this->upload('Administrative');

        $this->actingAs($this->makeUser('Reviewer'))->getJson("/api/records/{$record->id}/view")->assertForbidden();
        $this->actingAs($this->makeUser('Reviewer'))->getJson("/api/records/{$record->id}/download")->assertForbidden();
        $this->actingAs($this->makeUser('Administrative'))->get("/api/records/{$record->id}/view")->assertOk();
    }

    public function test_the_file_is_kept_in_the_trash_and_removed_on_permanent_delete(): void
    {
        $record = $this->upload('Provincial Budget');

        $record->delete();
        Storage::disk(Record::FILE_DISK)->assertExists($record->image_path);

        $record->forceDelete();
        Storage::disk(Record::FILE_DISK)->assertMissing($record->image_path);
    }

    public function test_existing_public_files_are_moved_by_the_migration(): void
    {
        Storage::disk('public')->put('records/old-upload.pdf', 'old file');
        $record = Record::create(['record_no' => 'PB-2026-01-001', 'record_type' => 'Provincial Budget', 'title' => 'Old', 'image_path' => 'records/old-upload.pdf', 'user_id' => $this->makeUser('Administrator')->id]);

        (require database_path('migrations/2026_10_27_000000_move_record_files_to_private_storage.php'))->up();

        Storage::disk('public')->assertMissing('records/old-upload.pdf');
        $this->assertSame('old file', Storage::disk(Record::FILE_DISK)->get('records/old-upload.pdf'));
        $this->assertTrue($record->fresh()->hasFile());
    }
}
