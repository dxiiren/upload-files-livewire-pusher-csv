<?php

namespace App\Events;

use App\Models\File;
use App\Enums\FileStatusEnum;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class FileUploaded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public File $file, public FileStatusEnum $status)
    {
    }

    public function broadcastOn()
    {
        return new Channel('uploads');
    }
}
