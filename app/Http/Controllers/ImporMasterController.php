<?php

namespace App\Http\Controllers;

use App\Support\ImporMaster\TemplateJurusanExport;
use App\Support\ImporMaster\TemplateKelasExport;
use App\Support\ImporMaster\TemplateTarifExport;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImporMasterController extends Controller
{
    /**
     * Download Template Impor Jurusan (Excel).
     */
    public function templateJurusan(): BinaryFileResponse
    {
        abort_unless(auth()->user()?->isAdmin(), 403, 'Akses terbatas untuk Administrator.');

        return Excel::download(new TemplateJurusanExport, 'template-impor-jurusan.xlsx');
    }

    /**
     * Download Template Impor Kelas (Excel).
     */
    public function templateKelas(): BinaryFileResponse
    {
        abort_unless(auth()->user()?->isAdmin(), 403, 'Akses terbatas untuk Administrator.');

        return Excel::download(new TemplateKelasExport, 'template-impor-kelas.xlsx');
    }

    /**
     * Download Template Impor Tarif SPP (Excel).
     */
    public function templateTarif(): BinaryFileResponse
    {
        abort_unless(auth()->user()?->isAdmin(), 403, 'Akses terbatas untuk Administrator.');

        return Excel::download(new TemplateTarifExport, 'template-impor-tarif-spp.xlsx');
    }
}
