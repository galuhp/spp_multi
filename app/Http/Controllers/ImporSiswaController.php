<?php

namespace App\Http\Controllers;

use App\Models\ImportSiswaStaging;
use App\Support\ImporSiswa\LaporanErrorExport;
use App\Support\ImporSiswa\TemplateImporExport;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImporSiswaController extends Controller
{
    /**
     * Download the import template with instructions and reference data.
     */
    public function template(): BinaryFileResponse
    {
        return Excel::download(new TemplateImporExport, 'template-impor-siswa.xlsx');
    }

    /**
     * Download the error report of one import batch.
     */
    public function laporanError(string $batchId): BinaryFileResponse
    {
        abort_unless(
            ImportSiswaStaging::query()->where('batch_id', $batchId)->exists(),
            404,
        );

        return Excel::download(
            new LaporanErrorExport($batchId),
            "laporan-error-impor-{$batchId}.xlsx",
        );
    }
}
