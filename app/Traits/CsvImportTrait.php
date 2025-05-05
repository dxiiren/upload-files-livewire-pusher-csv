<?php

namespace App\Traits;

use League\Csv\Reader;
use League\Csv\Statement;
use App\Data\RowRangeData;
use Spatie\LaravelData\DataCollection;
use Illuminate\Support\Facades\Storage;

trait CsvImportTrait
{
    protected function countRowsInSheet(string $path, string $disk = 'local'): int
    {
        $rowCount = 0;

        if (($handle = Storage::disk($disk)->readStream($path)) !== false) {

            fgetcsv($handle); // Skip the first line (header)

            while (fgetcsv($handle) !== false) {
                $rowCount++;
            }

            fclose($handle);
        }

        return $rowCount;
    }

    public function generateRowRanges(int $totalRows, int $rowsPerChunk = 500): DataCollection
    {
        $rowRanges = [];
        $startRow = 1;

        while ($startRow <= $totalRows) {
            $endRow = min($startRow + $rowsPerChunk - 1, $totalRows);
            $rowRanges[] = [$startRow, $endRow];
            $startRow = $endRow + 1;
        }

        return RowRangeData::collect($rowRanges, DataCollection::class);
    }

    public function readDataFromCsv(int $startRow, int $endRow, string $filePath, string $disk = 'local'): array
    {
        if ($startRow < 1 || $endRow < $startRow) {
            throw new \InvalidArgumentException("Invalid row range specified.");
        }

        $stream = $this->openStream($filePath, $disk);
        $reader = $this->createReader($stream);
        $header = $this->cleanHeader($reader->getHeader());

        $records = (new Statement())
            ->offset($startRow)
            ->limit($endRow - $startRow + 1)
            ->process($reader, $header); // Prepares lazy iterator, doesn't read yet

        $data = array_values(iterator_to_array($records)); // Reads stream here

        fclose($stream); // Close after reading

        return $data;
    }

    /**
     * @return resource
     */
    protected function openStream(string $filePath, string $disk)
    {
        $stream = Storage::disk($disk)->readStream($filePath);
        if (!$stream) {
            throw new \RuntimeException("Failed to open file: $filePath");
        }
        return $stream;
    }

    protected function createReader($stream): Reader
    {
        return Reader::createFromStream($stream)
            ->setDelimiter(',')
            ->setHeaderOffset(0);
    }

    protected function cleanHeader(array $header): array
    {
        $cleanHeader = [];
        $emptyColumnCount = 0;

        foreach ($header as $columnName) {
            if (empty($columnName)) {
                $columnName = "empty_column_" . ++$emptyColumnCount;
            }
            if (in_array($columnName, $cleanHeader)) {
                $columnName .= "_" . uniqid();
            }
            $cleanHeader[] = $columnName;
        }

        return $cleanHeader;
    }
}
