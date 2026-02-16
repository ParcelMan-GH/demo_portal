<?php

namespace App\Support;

use Barryvdh\DomPDF\Facade\Pdf;

class GenericPdfExporter
{
    /**
     * @param array<int, array<string, mixed>> $rows
     */
    public static function download(array $rows, string $filename, string $title): mixed
    {
        $headers = [];

        if (!empty($rows) && is_array($rows[0])) {
            $headers = array_keys($rows[0]);
        }

        $pdf = Pdf::loadView('admin.shared.export-pdf', [
            'title' => $title,
            'rows' => $rows,
            'headers' => $headers,
            'generatedAt' => now()->format('F d, Y H:i:s'),
        ]);

        return $pdf->download($filename);
    }
}

