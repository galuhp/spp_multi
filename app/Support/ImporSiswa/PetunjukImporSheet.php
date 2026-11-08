<?php

namespace App\Support\ImporSiswa;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PetunjukImporSheet implements FromArray, WithColumnWidths, WithStyles, WithTitle
{
    /**
     * @return array<int, array<int, string>>
     */
    public function array(): array
    {
        return [
            ['PETUNJUK PENGISIAN TEMPLATE IMPOR SISWA'],
            [''],
            ['1. Unduh template ini, lalu isi data pada sheet "Template" mulai baris 2.'],
            ['2. Jangan mengubah nama atau urutan kolom. Jangan menghapus baris judul.'],
            ['3. Kolom NISN, NIK, NIS, dan HP Wali sudah berformat Teks. Biarkan format tersebut agar angka nol di depan tidak hilang.'],
            ['4. NISN wajib tepat 10 digit angka bila diisi. NISN kosong diperbolehkan dan akan ditandai sebagai peringatan "tanpa NISN".'],
            ['5. NIK wajib tepat 16 digit angka bila diisi.'],
            ['6. NIS wajib diisi dan tidak boleh sama dengan siswa lain.'],
            ['7. Jenis kelamin hanya boleh L atau P.'],
            ['8. Tanggal ditulis dengan format YYYY-MM-DD (contoh 2026-07-15) atau DD/MM/YYYY. Tahun Masuk (Angkatan) diambil otomatis dari tahun Tanggal Masuk.'],
            ['9. Kode kompetensi dan nama kelas harus sesuai sheet "Daftar Referensi" (termasuk Master Kelas Lintas Tahun).'],
            ['10. Minimal satu dari Nama Ayah, Nama Ibu, atau Nama Wali wajib diisi.'],
            ['11. HP wali dinormalisasi menjadi 62xxxxxxxxxx. Nomor yang tidak valid dikosongkan dan baris tetap dapat disimpan.'],
            ['12. Batas berkas: .xlsx, maksimal 5 MB dan 2.000 baris data.'],
            ['13. Baris dengan error dilewati. Baris dengan peringatan tetap disimpan.'],
            ['14. Jika NISN/NIK/NIS sudah terdaftar, baris dilewati kecuali opsi "Perbarui data siswa yang sudah ada" dipilih.'],
            ['15. Kolom Potongan (%) bersifat opsional, diisi angka persentase subsidi/diskon SPP (contoh: 25 atau 50). Kosongkan jika membayar tarif penuh.'],
            [''],
            ['RENTANG UMUR YANG DIANGGAP WAJAR'],
            ['SD (Kelas 1–6): 6–12 tahun. SMP (Kelas 7–9): 12–15 tahun. SMA/SMK (Kelas X–XII): 15–18 tahun (±2 tahun).'],
            ['Umur di luar rentang tersebut hanya menjadi peringatan, bukan error.'],
        ];
    }

    /**
     * @return array<string, int>
     */
    public function columnWidths(): array
    {
        return [
            'A' => 120,
        ];
    }

    /**
     * @return array<int|string, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 13, 'color' => ['argb' => 'FF1E3A8A']]],
            18 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Petunjuk';
    }
}
