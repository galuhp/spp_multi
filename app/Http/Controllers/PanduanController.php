<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PanduanController extends Controller
{
    public function unduh(): BinaryFileResponse
    {
        $path = base_path('docs/PANDUAN_PENGGUNAAN.md');
        abort_unless(file_exists($path), 404);

        return response()->download($path, 'Buku-Panduan-E-SPP.md', [
            'Content-Type' => 'text/markdown; charset=UTF-8',
        ]);
    }
}
