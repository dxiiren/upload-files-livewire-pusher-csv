<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class File extends Model
{
    use HasFactory;

    protected $fillable = [
        'filename',
        'unique_filename',
        'path',
        'status',
    ];

    protected $table = 'files';

    protected function casts(): array
    {
        return [
            'status' => \App\Enums\FileStatusEnum::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
