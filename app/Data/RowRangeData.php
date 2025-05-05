<?php

namespace App\Data;

use Spatie\LaravelData\Data;

Class RowRangeData extends Data
{
    public function __construct(
        public int $start,
        public int $end,
    ) {}

    public static function fromArray(array $array): self
    {
        return new self(
            start: $array[0] ?? $array['start'],
            end: $array[1] ?? $array['end'],
        );
    }
}