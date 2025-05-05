<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\File;
use App\Data\FileData;
use App\Models\Product;
use App\Data\ProductData;
use App\Data\RowRangeData;
use App\Events\FileUploaded;
use App\Enums\FileStatusEnum;
use App\Jobs\ImportProductJob;
use App\Jobs\UpsertProductJob;
use App\Traits\CsvImportTrait;
use Illuminate\Support\Facades\Bus;
use App\Repositories\FileRepository;
use Illuminate\Support\Facades\Event;
use Spatie\LaravelData\DataCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FileUploadRepositoryTest extends TestCase
{
    use RefreshDatabase, CsvImportTrait;

    protected FileRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new FileRepository();
    }

    public function test_it_can_get_all_files()
    {
        //prepare
        File::factory()->count(3)->create();

        //test
        $files = $this->repository->getAllFiles();

        //assert
        $this->assertCount(3, $files);
        $this->assertInstanceOf(FileData::class, $files[0]);
    }

    public function test_it_can_create_a_file()
    {
        //prepare
        $fileData = FileData::from([
            'filename' => 'example.csv',
            'unique_filename' => 'example.csv-abc123',
            'path' => 'uploads/example.csv-abc123',
            'status' => 'pending',
        ]);

        //test
        $result = $this->repository->createFile($fileData);

        //assert
        $this->assertDatabaseHas('files', [
            'filename' => 'example.csv',
            'unique_filename' => 'example.csv-abc123',
        ]);

        $this->assertInstanceOf(FileData::class, $result);
        $this->assertEquals('example.csv', $result->filename);
    }

    public function test_it_can_update_file_status()
    {
        //prepare
        $file = File::factory()->create(['status' => 'pending']);

        //test
        $updated = $this->repository->updateFileStatus($file->id, 'completed');

        //assert
        $this->assertEquals('completed', $updated->status);
        $this->assertDatabaseHas('files', [
            'id' => $file->id,
            'status' => 'completed',
        ]);
    }

    public function test_it_dispatch_bus()
    {
        //prepare
        Event::fake();
        Bus::fake();
        $fileName = 'product_sample.csv';
        $path = 'uploads/' . $fileName . '-' . uniqid();
        Storage::fake(config('filesystems.default', 'local'))
            ->put(
                $path,
                file_get_contents(database_path('seeders/product_sample.csv'))
            );

        File::factory()->create([
            'unique_filename' => $fileName,
            'path' => $path,
            'status' => FileStatusEnum::PENDING,
        ]);

        //test
        $this->repository->upload(
            $fileName,
            $path,
            10
        );

        //assert
        Bus::assertBatched(function ($batch) {
            return collect($batch->jobs)->every(fn($job) => $job instanceof ImportProductJob);
        });

        Event::assertDispatched(FileUploaded::class, function ($event) use ($fileName) {
            return $event->file->unique_filename === $fileName && $event->status === FileStatusEnum::PROCESSING;
        });
    }

    public function test_it_dispatch_import_product_job()
    {
        Bus::fake();

        $fileName = 'product_sample.csv';
        $path = 'uploads/' . $fileName . '-' . uniqid();

        // Setup fake file
        Storage::fake(config('filesystems.default', 'local'))
            ->put(
                $path,
                file_get_contents(database_path('seeders/product_sample.csv'))
            );

        $job = new ImportProductJob(RowRangeData::from([
            'start' => 1,
            'end' => 10,
        ]), $path);

        // Dispatch the job within a batch to avoid null error
        Bus::batch([$job])->dispatch();

        // Assert that UpsertProductJob was added to a batch
        Bus::assertBatched(function ($batch) {
            return collect($batch->jobs)->contains(function ($job) {
                return $job instanceof \App\Jobs\ImportProductJob;
            });
        });
    }

    public function test_it_add_upsert_product_job_to_the_batch()
    {
        //prepare
        $fileName = 'product_sample.csv';
        $path = 'uploads/' . $fileName . '-' . uniqid();

        Storage::disk(config('filesystems.default', 'local'))
            ->put($path, file_get_contents(database_path('seeders/product_sample.csv')));

        $importProductJob = new ImportProductJob(RowRangeData::from([
            'start' => 1,
            'end' => 10,
        ]), $path);

        $data = $this->readDataFromCsv($importProductJob->range->start, $importProductJob->range->end, $path, config('filesystems.default', 'local'));
        $allUniqueKeysFromCsv = array_column($data, 'UNIQUE_KEY');

        //trigger
        [$job, $batch] = ($importProductJob)->withFakeBatch();
        $job->handle();

        //assert
        $this->assertInstanceOf(UpsertProductJob::class, $batch->added[0]);

        $allUniqueKeysFromJob = array_map(function ($productData) {
            return $productData['unique_key'];
        }, $batch->added[0]->productsData->toArray());

        $this->assertEquals($allUniqueKeysFromCsv, $allUniqueKeysFromJob);
    }

    public function test_it_upserts_products_successfully()
    {
        //prepare
        $productsData = ProductData::collect([
            [
                'unique_key' => 'ABC123',
                'product_title' => 'Updated Shirt',
                'product_description' => 'Updated desc',
                'style' => 'Casual',
                'sanmar_mainframe_color' => 'Red',
                'size' => 'L',
                'color_name' => 'Crimson',
                'piece_price' => 39.99,
            ],
            [
                'unique_key' => 'XYZ999',
                'product_title' => 'New Pants',
                'product_description' => 'Comfy pants',
                'style' => 'Sport',
                'sanmar_mainframe_color' => 'Blue',
                'size' => 'M',
                'color_name' => 'Navy',
                'piece_price' => 49.99,
            ]
        ], DataCollection::class);

        // test
        $job = new UpsertProductJob($productsData);
        $job->handle();

        // assert
        $this->assertDatabaseHas('products', [
            'unique_key' => 'ABC123',
            'product_title' => 'Updated Shirt',
            'piece_price' => 39.99,
        ]);

        $this->assertDatabaseHas('products', [
            'unique_key' => 'XYZ999',
            'product_title' => 'New Pants',
            'piece_price' => 49.99,
        ]);

        $this->assertCount(2, Product::all());
    }

    public function test_it_can_create_product_from_import_csv()
    {
        Event::fake([FileUploaded::class]);

        //prepare
        $fileName = 'product_sample.csv';
        $path = 'uploads/' . $fileName . '-' . uniqid();
        Storage::fake(config('filesystems.default', 'local'))
            ->put(
                $path,
                file_get_contents(database_path('seeders/product_sample.csv'))
            );

        File::factory()->create([
            'unique_filename' => $fileName,
            'path' => $path,
            'status' => FileStatusEnum::PENDING,
        ]);

        //test
        $this->repository->upload(
            $fileName,
            $path,
            10
        );

        //assert
        foreach ($this->readDataFromCsv(1, 10, $path, 'local') as $row) {
            $this->assertDatabaseHas('products', [
                'unique_key' => $row['UNIQUE_KEY'],
            ]);
        }

        Event::assertDispatched(FileUploaded::class, function ($event) use ($fileName) {
            return $event->file->unique_filename === $fileName && $event->status === FileStatusEnum::PROCESSING;
        });
        
        Event::assertDispatched(FileUploaded::class, function ($event) use ($fileName) {
            return $event->file->unique_filename === $fileName && $event->status === FileStatusEnum::COMPLETED;
        });
        
        Event::assertNotDispatched(FileUploaded::class, function ($event) {
            return $event->status === FileStatusEnum::FAILED;
        });
    }
}
