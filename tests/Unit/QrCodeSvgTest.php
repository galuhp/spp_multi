<?php

namespace Tests\Unit;

use App\Support\QrCodeSvg;
use PHPUnit\Framework\TestCase;

class QrCodeSvgTest extends TestCase
{
    public function test_generates_valid_svg_markup(): void
    {
        $svg = QrCodeSvg::generate('https://smknegericontoh.sch.id/bukti/SPP-20261103-0001/a1b2c3d4e5f60718', 96);

        $this->assertStringStartsWith('<svg xmlns="http://www.w3.org/2000/svg"', $svg);
        $this->assertStringContainsString('width="96" height="96"', $svg);
        $this->assertStringContainsString('aria-label="QR Code Bukti Pembayaran"', $svg);
        $this->assertStringContainsString('<path d="M', $svg);
    }

    public function test_encoded_qr_matrix_round_trips_and_passes_reed_solomon_syndromes(): void
    {
        $samples = [
            'SPP-0001',
            'http://localhost:8000/bukti/SPP-20261103-0001/9f86d081884c7d65',
            'https://spp.smknegericontoh.sch.id/bukti/SPP-20261103-0128/deadbeefcafebabe?source=qr-cetak-kuitansi-orang-tua-siswa',
        ];

        foreach ($samples as $expectedText) {
            $matrix = QrCodeSvg::encodeMatrix($expectedText);
            $decoded = $this->decodeQrMatrix($matrix);

            $this->assertSame($expectedText, $decoded);
        }
    }

    /**
     * Independently verify format BCH, Reed-Solomon syndromes, and Byte-mode payload of a QR matrix.
     *
     * @param  array<int, array<int, bool>>  $matrix
     */
    private function decodeQrMatrix(array $matrix): string
    {
        $size = count($matrix);
        $version = intdiv($size - 17, 4);
        $this->assertGreaterThanOrEqual(1, $version);
        $this->assertLessThanOrEqual(10, $version);

        // Verify dark module at (x=8, y=size-8)
        $this->assertTrue($matrix[$size - 8][8]);

        // Read both 15-bit format information copies
        $format1 = 0;
        for ($i = 0; $i <= 5; $i++) {
            $format1 |= ($matrix[$i][8] ? 1 : 0) << $i;
        }
        $format1 |= ($matrix[7][8] ? 1 : 0) << 6;
        $format1 |= ($matrix[8][8] ? 1 : 0) << 7;
        $format1 |= ($matrix[8][7] ? 1 : 0) << 8;
        for ($i = 9; $i < 15; $i++) {
            $format1 |= ($matrix[8][14 - $i] ? 1 : 0) << $i;
        }

        $format2 = 0;
        for ($i = 0; $i < 8; $i++) {
            $format2 |= ($matrix[8][$size - 1 - $i] ? 1 : 0) << $i;
        }
        for ($i = 8; $i < 15; $i++) {
            $format2 |= ($matrix[$size - 15 + $i][8] ? 1 : 0) << $i;
        }

        $this->assertSame($format1, $format2, 'Both QR format bit copies must match.');

        $unmaskedFormat = $format1 ^ 0x5412;
        // Verify BCH(15, 5) polynomial division remainder by 0x537 is zero
        $rem = $unmaskedFormat;
        for ($b = 14; $b >= 10; $b--) {
            if ((($rem >> $b) & 1) === 1) {
                $rem ^= (0x537 << ($b - 10));
            }
        }
        $this->assertSame(0, $rem, 'Format BCH syndrome must be zero.');

        $ecLevelIndicator = ($unmaskedFormat >> 13) & 0b11;
        $mask = ($unmaskedFormat >> 10) & 0b111;
        $this->assertSame(0, $ecLevelIndicator, 'Expected ECC Level M (00).');

        // Build function module mask to unmask data bits
        $isFunction = $this->buildFunctionMap($size, $version);

        $unmasked = $matrix;
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                if ($isFunction[$y][$x]) {
                    continue;
                }
                $invert = match ($mask) {
                    0 => (($y + $x) % 2) === 0,
                    1 => ($y % 2) === 0,
                    2 => ($x % 3) === 0,
                    3 => (($y + $x) % 3) === 0,
                    4 => ((intdiv($y, 2) + intdiv($x, 3)) % 2) === 0,
                    5 => ((($y * $x) % 2) + (($y * $x) % 3)) === 0,
                    6 => (((($y * $x) % 2) + (($y * $x) % 3)) % 2) === 0,
                    default => (((($y + $x) % 2) + (($y * $x) % 3)) % 2) === 0,
                };
                if ($invert) {
                    $unmasked[$y][$x] = ! $unmasked[$y][$x];
                }
            }
        }

        // Read zigzag bits
        $bits = [];
        for ($right = $size - 1; $right >= 1; $right -= 2) {
            if ($right === 6) {
                $right = 5;
            }
            $upward = (($right + 1) & 2) === 0;
            for ($vert = 0; $vert < $size; $vert++) {
                $y = $upward ? ($size - 1 - $vert) : $vert;
                for ($j = 0; $j < 2; $j++) {
                    $x = $right - $j;
                    if (! $isFunction[$y][$x]) {
                        $bits[] = $unmasked[$y][$x] ? 1 : 0;
                    }
                }
            }
        }

        $versionParams = [
            1 => [26, 10, 1, 16, 0],
            2 => [44, 16, 1, 28, 0],
            3 => [70, 26, 1, 44, 0],
            4 => [100, 18, 2, 32, 0],
            5 => [134, 24, 2, 43, 0],
            6 => [172, 16, 4, 27, 0],
            7 => [196, 18, 4, 31, 0],
            8 => [242, 22, 2, 38, 2],
            9 => [292, 22, 3, 36, 2],
            10 => [346, 26, 4, 43, 1],
        ];
        [$totalCodewords, $ecPerBlock, $g1Blocks, $g1DataLen, $g2Blocks] = $versionParams[$version];

        $codewords = [];
        for ($i = 0; $i < $totalCodewords; $i++) {
            $val = 0;
            for ($b = 0; $b < 8; $b++) {
                $val = ($val << 1) | $bits[($i * 8) + $b];
            }
            $codewords[] = $val;
        }

        // De-interleave blocks
        $totalBlocks = $g1Blocks + $g2Blocks;
        $dataBlocks = array_fill(0, $totalBlocks, []);
        $ecBlocks = array_fill(0, $totalBlocks, []);
        $maxDataLen = $g2Blocks > 0 ? ($g1DataLen + 1) : $g1DataLen;

        $idx = 0;
        for ($col = 0; $col < $maxDataLen; $col++) {
            for ($b = 0; $b < $totalBlocks; $b++) {
                $blockLen = $b < $g1Blocks ? $g1DataLen : ($g1DataLen + 1);
                if ($col < $blockLen) {
                    $dataBlocks[$b][] = $codewords[$idx++];
                }
            }
        }
        for ($col = 0; $col < $ecPerBlock; $col++) {
            for ($b = 0; $b < $totalBlocks; $b++) {
                $ecBlocks[$b][] = $codewords[$idx++];
            }
        }

        // Check Reed-Solomon syndromes S_0 .. S_{ecPerBlock-1} == 0 for every block
        for ($b = 0; $b < $totalBlocks; $b++) {
            $poly = array_merge($dataBlocks[$b], $ecBlocks[$b]);
            $alphaPow = 1;
            for ($s = 0; $s < $ecPerBlock; $s++) {
                $eval = 0;
                foreach ($poly as $coeff) {
                    $eval = $this->gfMul($eval, $alphaPow) ^ $coeff;
                }
                $this->assertSame(0, $eval, "Reed-Solomon syndrome {$s} for block {$b} must be 0.");
                $alphaPow = $this->gfMul($alphaPow, 0x02);
            }
        }

        // Concatenate data codewords and decode Byte-mode stream
        $dataBits = [];
        foreach ($dataBlocks as $block) {
            foreach ($block as $cw) {
                for ($b = 7; $b >= 0; $b--) {
                    $dataBits[] = ($cw >> $b) & 1;
                }
            }
        }

        $mode = ($dataBits[0] << 3) | ($dataBits[1] << 2) | ($dataBits[2] << 1) | $dataBits[3];
        $this->assertSame(0b0100, $mode, 'Expected Byte mode (0100).');

        $countBits = $version <= 9 ? 8 : 16;
        $charCount = 0;
        for ($i = 0; $i < $countBits; $i++) {
            $charCount = ($charCount << 1) | $dataBits[4 + $i];
        }

        $decodedBytes = [];
        $offset = 4 + $countBits;
        for ($c = 0; $c < $charCount; $c++) {
            $byte = 0;
            for ($b = 0; $b < 8; $b++) {
                $byte = ($byte << 1) | $dataBits[$offset++];
            }
            $decodedBytes[] = chr($byte);
        }

        return implode('', $decodedBytes);
    }

    /**
     * @return array<int, array<int, bool>>
     */
    private function buildFunctionMap(int $size, int $version): array
    {
        $map = array_fill(0, $size, array_fill(0, $size, false));

        for ($i = 0; $i < $size; $i++) {
            $map[$i][6] = true;
            $map[6][$i] = true;
        }

        foreach ([[3, 3], [$size - 4, 3], [3, $size - 4]] as [$cx, $cy]) {
            for ($dy = -4; $dy <= 4; $dy++) {
                for ($dx = -4; $dx <= 4; $dx++) {
                    $x = $cx + $dx;
                    $y = $cy + $dy;
                    if ($x >= 0 && $x < $size && $y >= 0 && $y < $size) {
                        $map[$y][$x] = true;
                    }
                }
            }
        }

        $alignTable = [
            1 => [],
            2 => [6, 18],
            3 => [6, 22],
            4 => [6, 26],
            5 => [6, 30],
            6 => [6, 34],
            7 => [6, 22, 38],
            8 => [6, 24, 42],
            9 => [6, 26, 46],
            10 => [6, 28, 50],
        ];
        $centers = $alignTable[$version];
        $num = count($centers);
        for ($i = 0; $i < $num; $i++) {
            for ($j = 0; $j < $num; $j++) {
                $isCorner = ($i === 0 && $j === 0) || ($i === 0 && $j === $num - 1) || ($i === $num - 1 && $j === 0);
                if (! $isCorner) {
                    for ($dy = -2; $dy <= 2; $dy++) {
                        for ($dx = -2; $dx <= 2; $dx++) {
                            $map[$centers[$i] + $dy][$centers[$j] + $dx] = true;
                        }
                    }
                }
            }
        }

        for ($i = 0; $i <= 8; $i++) {
            $map[$i][8] = true;
            $map[8][$i] = true;
        }
        for ($i = 0; $i < 8; $i++) {
            $map[8][$size - 1 - $i] = true;
            $map[$size - 1 - $i][8] = true;
        }

        if ($version >= 7) {
            for ($i = 0; $i < 18; $i++) {
                $a = $size - 11 + ($i % 3);
                $b = intdiv($i, 3);
                $map[$b][$a] = true;
                $map[$a][$b] = true;
            }
        }

        return $map;
    }

    private function gfMul(int $x, int $y): int
    {
        $z = 0;
        for ($i = 7; $i >= 0; $i--) {
            $z = ($z << 1) ^ (($z >> 7) * 0x11D);
            $z ^= (($y >> $i) & 1) * $x;
        }

        return $z;
    }
}
