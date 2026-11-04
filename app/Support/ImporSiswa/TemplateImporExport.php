<?php

namespace App\Support\ImporSiswa;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TemplateImporExport implements Export, WithMultipleSheets
{
    /**
     * @return array<string, object>
     */
    public function sheets(): array
    {
        return [
            'Template' => new TemplateSiswaSheet,
            'Petunjuk' => new PetunjukImporSheet,
            'Daftar Referensi' => new DaftarReferensiSheet,
        ];
    }
}
