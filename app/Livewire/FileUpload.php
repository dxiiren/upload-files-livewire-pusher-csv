<?php

namespace App\Livewire;

use App\Data\FileData;
use Livewire\Component;
use Livewire\Attributes\On;
use App\Enums\FileStatusEnum;
use Livewire\WithFileUploads;
use App\Repositories\FileRepository;
use Livewire\Attributes\Validate;

class FileUpload extends Component
{
    use WithFileUploads;

    #[Validate()]
    public $file;
    public $uploads = [];
    protected FileRepository $fileRepository;

    public $statusClasses = [
        'pending' => 'text-yellow-600',
        'processing' => 'text-blue-600',
        'completed' => 'text-green-600',
        'failed' => 'text-red-600',
    ];

    public function rules()
    {
        return [
            'file' => 'required|file|mimes:csv|max:51200', // 50MB max (in KB)
        ];
    }

    public function boot()
    {
        $this->fileRepository = app(FileRepository::class);
    }

    public function mount()
    {
        $this->uploads = $this->fileRepository->getAllFiles()->toArray();
    }

    public function updatedFile()
    {
        $this->validate();
        $fileData = FileData::from($this->file);
        $fileData = $this->fileRepository->createFile($fileData);

        array_unshift($this->uploads, $fileData->toArray());
        
        $this->fileRepository->upload(
            $fileData->unique_filename,
            $fileData->path,
            500
        );

        $this->file = null;
    }

    public function render()
    {
        return view('livewire.file-upload');
    }

    #[On('echo:uploads,FileUploaded')]
    public function fileUploadCompleted($payload)
    {
        foreach ($this->uploads as &$upload) {
            if ($upload['unique_filename'] === $payload['file']['unique_filename']) {

                $status = FileStatusEnum::from($payload['status']);
                $upload['status'] = $status->value;

                $this->fileRepository->updateFileStatus($upload['id'], $status->value);

                $this->dispatch('file-upload-status', [
                    'status' => $payload['status'],
                    'filename' => $payload['file']['filename'],
                ]);
            }
        }
    }
}
