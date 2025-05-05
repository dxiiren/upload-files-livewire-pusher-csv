<?php

namespace App\Enums;

enum FileStatusEnum: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}