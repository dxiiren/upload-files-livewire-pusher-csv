<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\File;
use Livewire\Livewire;
use App\Livewire\FileUpload;
use App\Enums\FileStatusEnum;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FileUploadComponentTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_render_the_component()
    {
        Livewire::test(FileUpload::class)
            ->assertStatus(200);
    }

    public function test_it_can_upload_a_file_and_create_a_record()
    {
        // Prepare
        Storage::fake(config('filesystems.default', 'local'));
        Event::fake();
    
        $file = UploadedFile::fake()->create('example.csv', 100, 'text/csv');
    
        // Test
        Livewire::test(FileUpload::class)
            ->set('file', $file);
    
        //assert
        $this->assertDatabaseHas('files', [
            'filename' => 'example.csv',
            'status' => FileStatusEnum::PENDING->value,
        ]);
    
        $stored = File::first();
        Storage::disk(config('filesystems.default', 'local'))->assertExists($stored->path);
    }
}
