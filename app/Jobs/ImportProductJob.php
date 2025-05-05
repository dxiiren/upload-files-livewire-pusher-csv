<?php

namespace App\Jobs;

use App\Data\ProductData;
use App\Data\RowRangeData;
use Illuminate\Bus\Batchable;
use App\Traits\CsvImportTrait;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ImportProductJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, CsvImportTrait;

    public function __construct(public RowRangeData $range, public $filePath) {}

    public function handle(): void
    {
        if ($this->batch()->cancelled()) {
            return;
        }

        $data = $this->readDataFromCsv($this->range->start, $this->range->end, $this->filePath, config('filesystems.default', 'local'));
        $productData = ProductData::buildCollectionFromCsv($data);

        $this->batch()->add(
            new UpsertProductJob($productData)
        );
    }
}
