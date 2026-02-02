<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UsersExport implements FromArray, WithHeadings
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private array $rows;

    /**
     * @var array<int, string>
     */
    private array $headings;

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    public function __construct(array $rows)
    {
        $this->rows = $rows;
        $this->headings = $rows ? array_keys($rows[0]) : [];
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return $this->headings;
    }

    /**
     * @return array<int, array<int, string>>
     */
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
