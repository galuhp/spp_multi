<?php

namespace Tests\Unit;

use App\Support\Terbilang;
use PHPUnit\Framework\TestCase;

class TerbilangTest extends TestCase
{
    public function test_converts_amounts_to_indonesian_words(): void
    {
        $samples = [
            0 => 'Nol rupiah',
            11 => 'Sebelas rupiah',
            12 => 'Dua belas rupiah',
            100 => 'Seratus rupiah',
            1001 => 'Seribu satu rupiah',
            3_475_000 => 'Tiga juta empat ratus tujuh puluh lima ribu rupiah',
            1_000_000_000 => 'Satu miliar rupiah',
            -1500 => 'Minus seribu lima ratus rupiah',
        ];

        foreach ($samples as $nominal => $expected) {
            $this->assertSame($expected, Terbilang::rupiah($nominal));
        }
    }
}
