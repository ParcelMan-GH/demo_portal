<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ShipmentsExport implements FromArray, WithHeadings
{
    private array $rows;
    private array $headings;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
        $this->headings = $rows ? array_keys($rows[0]) : [];
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function array(): array
    {
        if (!$this->rows) {
            return [];
        }

        return array_map(function (array $row) {
            return array_map(function (string $heading) use ($row) {
                $value = $row[$heading] ?? '';
                return is_scalar($value) ? (string) $value : json_encode($value);
            }, $this->headings);
        }, $this->rows);
    }
}
