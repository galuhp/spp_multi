<?php

namespace App\Support\ImporSiswa;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

class PembacaNilai
{
    /**
     * Read a cell as text without turning identity numbers into numeric notation.
     */
    public static function teks(mixed $nilai): ?string
    {
        if ($nilai === null) {
            return null;
        }

        if (is_bool($nilai)) {
            return $nilai ? '1' : '0';
        }

        if (is_int($nilai)) {
            return (string) $nilai;
        }

        if (is_float($nilai)) {
            if (! is_finite($nilai)) {
                return null;
            }

            return floor($nilai) === $nilai
                ? sprintf('%.0f', $nilai)
                : rtrim(rtrim(sprintf('%.15F', $nilai), '0'), '.');
        }

        $teks = trim((string) $nilai);

        if ($teks === '') {
            return null;
        }

        // Sel yang tersimpan sebagai angka ilmiah (mis. 1,234567890123456E+15).
        if (preg_match('/^[+-]?\d+(?:[.,]\d+)?[eE][+-]?\d+$/', $teks) === 1) {
            return sprintf('%.0f', (float) str_replace(',', '.', $teks));
        }

        return $teks;
    }

    /**
     * Normalize a date cell to Y-m-d. Invalid input is returned as-is for reporting.
     */
    public static function tanggal(mixed $nilai): ?string
    {
        if ($nilai === null) {
            return null;
        }

        if (is_int($nilai) || is_float($nilai)) {
            if ($nilai <= 0 || $nilai > 100000) {
                return self::teks($nilai);
            }

            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject($nilai))->format('Y-m-d');
            } catch (Throwable) {
                return self::teks($nilai);
            }
        }

        $teks = trim((string) $nilai);

        if ($teks === '') {
            return null;
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y', 'Y/m/d', 'j/n/Y', 'j-n-Y'] as $format) {
            if (! Carbon::hasFormat($teks, $format)) {
                continue;
            }

            try {
                return Carbon::createFromFormat('!'.$format, $teks)->format('Y-m-d');
            } catch (Throwable) {
                // Lanjut ke format berikutnya.
            }
        }

        try {
            return Carbon::parse($teks)->format('Y-m-d');
        } catch (Throwable) {
            return $teks;
        }
    }

    /**
     * Normalize an Indonesian phone number to 62xxxxxxxxxx.
     */
    public static function normalisasiHp(?string $nomor): ?string
    {
        if ($nomor === null) {
            return null;
        }

        $angka = preg_replace('/\D+/', '', $nomor);

        if ($angka === null || $angka === '') {
            return null;
        }

        if (str_starts_with($angka, '0')) {
            $angka = '62'.substr($angka, 1);
        } elseif (str_starts_with($angka, '8')) {
            $angka = '62'.$angka;
        }

        return preg_match('/^62\d{8,13}$/', $angka) === 1 ? $angka : null;
    }

    /**
     * Normalize a competency code for matching.
     */
    public static function normalisasiKode(?string $kode): string
    {
        return mb_strtoupper(trim((string) $kode));
    }

    /**
     * Normalize a class name for matching.
     */
    public static function normalisasiKelas(?string $kelas): string
    {
        return mb_strtoupper(preg_replace('/\s+/u', ' ', trim((string) $kelas)) ?? '');
    }

    /**
     * Determine whether a name contains digits or unusual symbols.
     */
    public static function mengandungSimbolAneh(?string $nama): bool
    {
        if ($nama === null || trim($nama) === '') {
            return false;
        }

        return preg_match('/[0-9]/', $nama) === 1
            || preg_match('/[^\p{L}\s.\'\-]/u', $nama) === 1;
    }

    /**
     * Determine whether a spreadsheet row has no value at all.
     *
     * @param  array<int, mixed>  $baris
     */
    public static function barisKosong(array $baris): bool
    {
        foreach ($baris as $nilai) {
            if ($nilai !== null && trim((string) $nilai) !== '') {
                return false;
            }
        }

        return true;
    }
}
