<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class ProductData extends Data
{
    public function __construct(
        public string $unique_key,
        public string $product_title,
        public ?string $product_description,
        public ?string $style,
        public ?string $sanmar_mainframe_color,
        public ?string $size,
        public ?string $color_name,
        public ?float $piece_price
    ) {
    }

    public static function buildCollectionFromCsv(array $data): DataCollection
    {
        $productData = [];
    
        foreach ($data as $row) {
            $cleanRow = array_map(
                fn($value) => is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'UTF-8') : $value,
                $row
            );
    
            $productData[] = new self(
                unique_key: (string) $cleanRow['UNIQUE_KEY'] ?? '',
                product_title: $cleanRow['PRODUCT_TITLE'] ?? '',
                product_description: $cleanRow['PRODUCT_DESCRIPTION'] ?? null,
                style: $cleanRow['STYLE#'] ?? null,
                sanmar_mainframe_color: $cleanRow['SANMAR_MAINFRAME_COLOR'] ?? null,
                size: $cleanRow['SIZE'] ?? null,
                color_name: $cleanRow['COLOR_NAME'] ?? null,
                piece_price: isset($cleanRow['PIECE_PRICE']) ? (float) $cleanRow['PIECE_PRICE'] : null
            );
        }
    
        return self::collect($productData, DataCollection::class);
    }
}