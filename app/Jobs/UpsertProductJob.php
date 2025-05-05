<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\DataCollection;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UpsertProductJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(public DataCollection $productsData) {}

    public function handle(): void
    {
        DB::transaction(function () {
            foreach (array_chunk($this->productsData->toArray(), 50) as $chunk) {
                DB::table('products')->upsert($chunk, ['unique_key'], [
                    'product_title',
                    'product_description',
                    'style',
                    'sanmar_mainframe_color',
                    'size',
                    'color_name',
                    'piece_price'
                ]);
            }
        }, 5);
    }
}
