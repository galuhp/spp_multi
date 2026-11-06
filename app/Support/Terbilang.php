<?php

namespace App\Support;

class Terbilang
{
    /**
     * @var array<int, string>
     */
    private const SATUAN = [
        '', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas',
    ];

    /**
     * Convert an amount to Indonesian words with the Rupiah unit.
     */
    public static function rupiah(int $nominal): string
    {
        $kata = $nominal < 0
            ? 'minus '.self::angka(abs($nominal))
            : self::angka($nominal);

        return ucfirst($kata).' rupiah';
    }

    /**
     * Convert a non-negative integer to Indonesian words.
     */
    public static function angka(int $angka): string
    {
        if ($angka < 12) {
            return self::SATUAN[$angka] ?: 'nol';
        }

        if ($angka < 20) {
            return self::SATUAN[$angka - 10].' belas';
        }

        if ($angka < 100) {
            return self::SATUAN[intdiv($angka, 10)].' puluh'.self::sisa($angka % 10);
        }

        if ($angka < 200) {
            return 'seratus'.self::sisa($angka - 100);
        }

        if ($angka < 1000) {
            return self::SATUAN[intdiv($angka, 100)].' ratus'.self::sisa($angka % 100);
        }

        if ($angka < 2000) {
            return 'seribu'.self::sisa($angka - 1000);
        }

        if ($angka < 1_000_000) {
            return self::angka(intdiv($angka, 1000)).' ribu'.self::sisa($angka % 1000);
        }

        if ($angka < 1_000_000_000) {
            return self::angka(intdiv($angka, 1_000_000)).' juta'.self::sisa($angka % 1_000_000);
        }

        if ($angka < 1_000_000_000_000) {
            return self::angka(intdiv($angka, 1_000_000_000)).' miliar'.self::sisa($angka % 1_000_000_000);
        }

        return self::angka(intdiv($angka, 1_000_000_000_000)).' triliun'.self::sisa($angka % 1_000_000_000_000);
    }

    /**
     * Append the remaining words when the remainder is not zero.
     */
    private static function sisa(int $angka): string
    {
        return $angka > 0 ? ' '.self::angka($angka) : '';
    }
}
