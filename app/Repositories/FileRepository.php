<?php

namespace App\Repositories;

use Throwable;
use App\Models\File;
use App\Data\FileData;
use Illuminate\Bus\Batch;
use App\Data\RowRangeData;
use App\Events\FileUploaded;
use App\Enums\FileStatusEnum;
use App\Jobs\ImportProductJob;
use App\Traits\CsvImportTrait;
use Illuminate\Support\Facades\Bus;
use Spatie\LaravelData\DataCollection;

class FileRepository
{
    use CsvImportTrait;

    public function getAllFiles(): DataCollection
    {
        $files = File::latest()->get();
        return FileData::collect($files, DataCollection::class);
    }

    public function createFile(FileData $fileData): FileData
    {
        $file = File::create($fileData->toArray());
        return FileData::from($file);
    }

    public function updateFileStatus($fileId, $status): FileData
    {
        $file = File::find($fileId);
        if ($file) {
            $file->status = $status;
            $file->save();
        }
        return FileData::from($file);
    }

    public function upload(string $fileName, string $path, int $chunkSize = 500): void
    {

        $importJobs = [];
        $totalRows = $this->countRowsInSheet($path, config('filesystems.default', 'local'));
        $rowRanges = $this->generateRowRanges($totalRows, $chunkSize);

        $file = File::where('unique_filename', $fileName)->firstOrFail();
        broadcast(new FileUploaded($file, FileStatusEnum::PROCESSING));

        /** @var RowRangeData $range */
        foreach ($rowRanges as $range) {
            $importJobs[] = new ImportProductJob($range, $path);
        }

        $batch = Bus::batch($importJobs)
            ->then(function (Batch $batch) use ($file) {
                // The batch has finished executing...
                broadcast(new FileUploaded($file, FileStatusEnum::COMPLETED));
            })
            ->catch(function (Batch $batch, Throwable $e) use ($file) {
                // First batch job failure detected..

                broadcast(new FileUploaded($file, FileStatusEnum::FAILED));
            })
            ->dispatch();
    }
}
