<?php

namespace App\Data;

use App\Models\File;
use Spatie\LaravelData\Data;
use App\Enums\FileStatusEnum;
use Spatie\LaravelData\Optional;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class FileData extends Data
{
    public function __construct(
        public Optional|int $id,
        public string $filename,
        public string $unique_filename,
        public string $path,
        public string $status,
        public Optional|string $created_at,
    ) {}

    public static function fromTemporaryUploadedFile(TemporaryUploadedFile $file): self
    {
        $filename = $file->getClientOriginalName();
        $uniqueFilename = $filename . '-' . uniqid();
        $path = $file->storeAs('uploads', $uniqueFilename, config('filesystems.default', 'local'));

        $fileData = [
            'filename' => $filename,
            'unique_filename' => $uniqueFilename,
            'path' => $path,
            'status' => FileStatusEnum::PENDING->value,
        ];

        return self::from($fileData);
    }

    public static function fromModel(File $file): self
    {
        $fileData = [
            'id' => $file->id,
            'filename' => $file->filename,
            'unique_filename' => $file->unique_filename,
            'path' => $file->path,
            'status' => $file->status->value,
            'created_at' => $file->created_at,
        ];

        return self::from($fileData);
    }
}
