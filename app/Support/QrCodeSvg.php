<?php

namespace App\Support;

use InvalidArgumentException;

class QrCodeSvg
{
    /**
     * Capacity and Reed-Solomon block parameters for Versions 1..10 at Error Correction Level M (format bits 00).
     * Each entry: [totalCodewords, ecCodewordsPerBlock, group1Blocks, group1DataCodewords, group2Blocks, remainderBits, alignmentCenters]
     *
     * @var array<int, array{0: int, 1: int, 2: int, 3: int, 4: int, 5: int, 6: array<int, int>}>
     */
    private const VERSION_TABLE_M = [
        1 => [26, 10, 1, 16, 0, 0, []],
        2 => [44, 16, 1, 28, 0, 7, [6, 18]],
        3 => [70, 26, 1, 44, 0, 7, [6, 22]],
        4 => [100, 18, 2, 32, 0, 7, [6, 26]],
        5 => [134, 24, 2, 43, 0, 7, [6, 30]],
        6 => [172, 16, 4, 27, 0, 7, [6, 34]],
        7 => [196, 18, 4, 31, 0, 0, [6, 22, 38]],
        8 => [242, 22, 2, 38, 2, 0, [6, 24, 42]],
        9 => [292, 22, 3, 36, 2, 0, [6, 26, 46]],
        10 => [346, 26, 4, 43, 1, 0, [6, 28, 50]],
    ];

    /**
     * Render an inline SVG QR Code for the given UTF-8 / byte string.
     */
    public static function generate(string $text, int $sizePx = 96, int $margin = 2): string
    {
        $matrix = self::encodeMatrix($text);
        $moduleCount = count($matrix);
        $viewSize = $moduleCount + ($margin * 2);

        $pathParts = [];
        for ($y = 0; $y < $moduleCount; $y++) {
            for ($x = 0; $x < $moduleCount; $x++) {
                if ($matrix[$y][$x]) {
                    $px = $x + $margin;
                    $py = $y + $margin;
                    $pathParts[] = "M{$px},{$py}h1v1h-1z";
                }
            }
        }

        $pathData = implode('', $pathParts);

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d" width="%d" height="%d" shape-rendering="crispEdges" role="img" aria-label="QR Code Bukti Pembayaran"><rect width="100%%" height="100%%" fill="#ffffff"/><path d="%s" fill="#0f172a"/></svg>',
            $viewSize,
            $viewSize,
            $sizePx,
            $sizePx,
            $pathData
        );
    }

    /**
     * Encode text into a 2D boolean QR module matrix (true = dark, false = light).
     *
     * @return array<int, array<int, bool>>
     */
    public static function encodeMatrix(string $text): array
    {
        $bytes = array_values(unpack('C*', $text) ?: []);
        $byteLength = count($bytes);

        $version = self::selectVersion($byteLength);
        [$totalCodewords, $ecPerBlock, $g1Blocks, $g1DataLen, $g2Blocks, $remainderBits, $alignCenters] = self::VERSION_TABLE_M[$version];

        $dataCodewordsCount = ($g1Blocks * $g1DataLen) + ($g2Blocks * ($g1DataLen + 1));
        $dataCodewords = self::encodeDataCodewords($bytes, $version, $dataCodewordsCount);
        $allCodewords = self::interleaveWithEcc($dataCodewords, $ecPerBlock, $g1Blocks, $g1DataLen, $g2Blocks);

        if (count($allCodewords) !== $totalCodewords) {
            throw new InvalidArgumentException('QR codeword count mismatch.');
        }

        $bitStream = [];
        foreach ($allCodewords as $cw) {
            for ($b = 7; $b >= 0; $b--) {
                $bitStream[] = (($cw >> $b) & 1) === 1;
            }
        }
        for ($r = 0; $r < $remainderBits; $r++) {
            $bitStream[] = false;
        }

        $size = ($version * 4) + 17;
        $modules = array_fill(0, $size, array_fill(0, $size, false));
        $isFunction = array_fill(0, $size, array_fill(0, $size, false));

        self::drawFunctionPatterns($modules, $isFunction, $version, $alignCenters);
        self::drawCodewords($modules, $isFunction, $bitStream);

        $bestMask = 0;
        $bestPenalty = PHP_INT_MAX;
        $bestMatrix = $modules;

        for ($mask = 0; $mask < 8; $mask++) {
            $candidate = $modules;
            self::applyMask($candidate, $isFunction, $mask);
            self::drawFormatBits($candidate, $isFunction, $mask);
            $penalty = self::calculatePenalty($candidate);

            if ($penalty < $bestPenalty) {
                $bestPenalty = $penalty;
                $bestMask = $mask;
                $bestMatrix = $candidate;
            }
        }

        return $bestMatrix;
    }

    /**
     * Select the smallest QR version (1..10) that fits the byte payload at Level M.
     */
    private static function selectVersion(int $byteLength): int
    {
        foreach (self::VERSION_TABLE_M as $version => $params) {
            $dataCodewords = ($params[2] * $params[3]) + ($params[4] * ($params[3] + 1));
            $countBits = $version <= 9 ? 8 : 16;
            $availableBytes = intdiv(($dataCodewords * 8) - 4 - $countBits, 8);

            if ($byteLength <= $availableBytes) {
                return $version;
            }
        }

        throw new InvalidArgumentException('Teks terlalu panjang untuk QR Code (maksimal 213 byte).');
    }

    /**
     * Build Byte-mode data codewords with terminator and pad bytes.
     *
     * @param  array<int, int>  $bytes
     * @return array<int, int>
     */
    private static function encodeDataCodewords(array $bytes, int $version, int $dataCodewordsCount): array
    {
        $bits = [];
        // Byte mode indicator: 0100
        self::appendBits($bits, 0b0100, 4);

        // Character count indicator
        $countBits = $version <= 9 ? 8 : 16;
        self::appendBits($bits, count($bytes), $countBits);

        foreach ($bytes as $byte) {
            self::appendBits($bits, $byte, 8);
        }

        $capacityBits = $dataCodewordsCount * 8;
        $terminatorLen = min(4, $capacityBits - count($bits));
        self::appendBits($bits, 0, $terminatorLen);

        $padZeroBits = (8 - (count($bits) % 8)) % 8;
        self::appendBits($bits, 0, $padZeroBits);

        $codewords = [];
        $bitCount = count($bits);
        for ($i = 0; $i < $bitCount; $i += 8) {
            $val = 0;
            for ($j = 0; $j < 8; $j++) {
                $val = ($val << 1) | $bits[$i + $j];
            }
            $codewords[] = $val;
        }

        $padBytes = [0xEC, 0x11];
        $padIndex = 0;
        while (count($codewords) < $dataCodewordsCount) {
            $codewords[] = $padBytes[$padIndex % 2];
            $padIndex++;
        }

        return $codewords;
    }

    /**
     * Append integer bits (MSB first) to bit array.
     *
     * @param  array<int, int>  $bits
     */
    private static function appendBits(array &$bits, int $value, int $length): void
    {
        for ($i = $length - 1; $i >= 0; $i--) {
            $bits[] = ($value >> $i) & 1;
        }
    }

    /**
     * Compute Reed-Solomon ECC per block and interleave data + ECC codewords.
     *
     * @param  array<int, int>  $dataCodewords
     * @return array<int, int>
     */
    private static function interleaveWithEcc(
        array $dataCodewords,
        int $ecPerBlock,
        int $g1Blocks,
        int $g1DataLen,
        int $g2Blocks
    ): array {
        $generator = self::reedSolomonGenerator($ecPerBlock);
        $dataBlocks = [];
        $ecBlocks = [];
        $offset = 0;
        $totalBlocks = $g1Blocks + $g2Blocks;

        for ($b = 0; $b < $totalBlocks; $b++) {
            $blockLen = $b < $g1Blocks ? $g1DataLen : ($g1DataLen + 1);
            $block = array_slice($dataCodewords, $offset, $blockLen);
            $offset += $blockLen;

            $dataBlocks[] = $block;
            $ecBlocks[] = self::reedSolomonRemainder($block, $generator);
        }

        $result = [];
        $maxDataLen = $g2Blocks > 0 ? ($g1DataLen + 1) : $g1DataLen;

        for ($col = 0; $col < $maxDataLen; $col++) {
            foreach ($dataBlocks as $block) {
                if (isset($block[$col])) {
                    $result[] = $block[$col];
                }
            }
        }

        for ($col = 0; $col < $ecPerBlock; $col++) {
            foreach ($ecBlocks as $ecBlock) {
                $result[] = $ecBlock[$col];
            }
        }

        return $result;
    }

    /**
     * Build Reed-Solomon divisor polynomial coefficients (excluding leading x^degree term).
     *
     * @return array<int, int>
     */
    private static function reedSolomonGenerator(int $degree): array
    {
        $result = array_fill(0, $degree, 0);
        $result[$degree - 1] = 1;
        $root = 1;

        for ($i = 0; $i < $degree; $i++) {
            for ($j = 0; $j < $degree; $j++) {
                $result[$j] = self::gfMultiply($result[$j], $root);
                if ($j + 1 < $degree) {
                    $result[$j] ^= $result[$j + 1];
                }
            }
            $root = self::gfMultiply($root, 0x02);
        }

        return $result;
    }

    /**
     * Compute Reed-Solomon remainder bytes for a data block.
     *
     * @param  array<int, int>  $data
     * @param  array<int, int>  $divisor
     * @return array<int, int>
     */
    private static function reedSolomonRemainder(array $data, array $divisor): array
    {
        $degree = count($divisor);
        $remainder = array_fill(0, $degree, 0);

        foreach ($data as $byte) {
            $factor = $byte ^ array_shift($remainder);
            $remainder[] = 0;
            for ($i = 0; $i < $degree; $i++) {
                $remainder[$i] ^= self::gfMultiply($divisor[$i], $factor);
            }
        }

        return $remainder;
    }

    /**
     * Multiply two elements in GF(2^8) modulo 0x11D.
     */
    private static function gfMultiply(int $x, int $y): int
    {
        $z = 0;
        for ($i = 7; $i >= 0; $i--) {
            $z = ($z << 1) ^ (($z >> 7) * 0x11D);
            $z ^= (($y >> $i) & 1) * $x;
        }

        return $z;
    }

    /**
     * Draw finders, separators, timing patterns, alignment patterns, dark module, format reservations, and version info.
     *
     * @param  array<int, array<int, bool>>  $modules
     * @param  array<int, array<int, bool>>  $isFunction
     * @param  array<int, int>  $alignCenters
     */
    private static function drawFunctionPatterns(
        array &$modules,
        array &$isFunction,
        int $version,
        array $alignCenters
    ): void {
        $size = count($modules);

        // Timing patterns
        for ($i = 0; $i < $size; $i++) {
            self::setFunctionModule($modules, $isFunction, 6, $i, ($i % 2) === 0);
            self::setFunctionModule($modules, $isFunction, $i, 6, ($i % 2) === 0);
        }

        // Finder patterns + separators (centers at (3,3), (size-4,3), (3,size-4))
        self::drawFinderPattern($modules, $isFunction, 3, 3);
        self::drawFinderPattern($modules, $isFunction, $size - 4, 3);
        self::drawFinderPattern($modules, $isFunction, 3, $size - 4);

        // Alignment patterns
        $numAlign = count($alignCenters);
        for ($i = 0; $i < $numAlign; $i++) {
            for ($j = 0; $j < $numAlign; $j++) {
                $isCorner = ($i === 0 && $j === 0)
                    || ($i === 0 && $j === $numAlign - 1)
                    || ($i === $numAlign - 1 && $j === 0);

                if (! $isCorner) {
                    self::drawAlignmentPattern($modules, $isFunction, $alignCenters[$j], $alignCenters[$i]);
                }
            }
        }

        // Reserve format bits + dark module
        self::drawFormatBits($modules, $isFunction, 0);

        // Version information (Version >= 7)
        if ($version >= 7) {
            $rem = $version;
            for ($i = 0; $i < 12; $i++) {
                $rem = ($rem << 1) ^ (($rem >> 11) * 0x1F25);
            }
            $bits = ($version << 12) | $rem;

            for ($i = 0; $i < 18; $i++) {
                $dark = (($bits >> $i) & 1) === 1;
                $a = $size - 11 + ($i % 3);
                $b = intdiv($i, 3);
                self::setFunctionModule($modules, $isFunction, $a, $b, $dark);
                self::setFunctionModule($modules, $isFunction, $b, $a, $dark);
            }
        }
    }

    /**
     * Draw a 7x7 finder pattern with 1-module white separator centered at (cx, cy).
     *
     * @param  array<int, array<int, bool>>  $modules
     * @param  array<int, array<int, bool>>  $isFunction
     */
    private static function drawFinderPattern(array &$modules, array &$isFunction, int $cx, int $cy): void
    {
        $size = count($modules);
        for ($dy = -4; $dy <= 4; $dy++) {
            for ($dx = -4; $dx <= 4; $dx++) {
                $x = $cx + $dx;
                $y = $cy + $dy;
                if ($x >= 0 && $x < $size && $y >= 0 && $y < $size) {
                    $dist = max(abs($dx), abs($dy));
                    self::setFunctionModule($modules, $isFunction, $x, $y, $dist !== 2 && $dist !== 4);
                }
            }
        }
    }

    /**
     * Draw a 5x5 alignment pattern centered at (cx, cy).
     *
     * @param  array<int, array<int, bool>>  $modules
     * @param  array<int, array<int, bool>>  $isFunction
     */
    private static function drawAlignmentPattern(array &$modules, array &$isFunction, int $cx, int $cy): void
    {
        for ($dy = -2; $dy <= 2; $dy++) {
            for ($dx = -2; $dx <= 2; $dx++) {
                $dist = max(abs($dx), abs($dy));
                self::setFunctionModule($modules, $isFunction, $cx + $dx, $cy + $dy, $dist !== 1);
            }
        }
    }

    /**
     * Draw 15-bit format information (ECC Level M = 00) and dark module.
     *
     * @param  array<int, array<int, bool>>  $modules
     * @param  array<int, array<int, bool>>  $isFunction
     */
    private static function drawFormatBits(array &$modules, array &$isFunction, int $mask): void
    {
        $size = count($modules);
        // ECC Level M format indicator is 00 (0)
        $data = (0 << 3) | $mask;
        $rem = $data;
        for ($i = 0; $i < 10; $i++) {
            $rem = ($rem << 1) ^ (($rem >> 9) * 0x537);
        }
        $bits = (($data << 10) | $rem) ^ 0x5412;

        // First copy (around top-left finder)
        for ($i = 0; $i <= 5; $i++) {
            self::setFunctionModule($modules, $isFunction, 8, $i, (($bits >> $i) & 1) === 1);
        }
        self::setFunctionModule($modules, $isFunction, 8, 7, (($bits >> 6) & 1) === 1);
        self::setFunctionModule($modules, $isFunction, 8, 8, (($bits >> 7) & 1) === 1);
        self::setFunctionModule($modules, $isFunction, 7, 8, (($bits >> 8) & 1) === 1);
        for ($i = 9; $i < 15; $i++) {
            self::setFunctionModule($modules, $isFunction, 14 - $i, 8, (($bits >> $i) & 1) === 1);
        }

        // Second copy (top-right horizontal & bottom-left vertical)
        for ($i = 0; $i < 8; $i++) {
            self::setFunctionModule($modules, $isFunction, $size - 1 - $i, 8, (($bits >> $i) & 1) === 1);
        }
        for ($i = 8; $i < 15; $i++) {
            self::setFunctionModule($modules, $isFunction, 8, $size - 15 + $i, (($bits >> $i) & 1) === 1);
        }

        // Always-dark module at (x=8, y=size-8)
        self::setFunctionModule($modules, $isFunction, 8, $size - 8, true);
    }

    /**
     * Mark a function module at column x, row y.
     *
     * @param  array<int, array<int, bool>>  $modules
     * @param  array<int, array<int, bool>>  $isFunction
     */
    private static function setFunctionModule(array &$modules, array &$isFunction, int $x, int $y, bool $dark): void
    {
        $modules[$y][$x] = $dark;
        $isFunction[$y][$x] = true;
    }

    /**
     * Place data and ECC bits in upward/downward 2-column zigzag scan.
     *
     * @param  array<int, array<int, bool>>  $modules
     * @param  array<int, array<int, bool>>  $isFunction
     * @param  array<int, bool>  $bitStream
     */
    private static function drawCodewords(array &$modules, array &$isFunction, array $bitStream): void
    {
        $size = count($modules);
        $bitIndex = 0;
        $bitTotal = count($bitStream);

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
                        $modules[$y][$x] = $bitIndex < $bitTotal ? $bitStream[$bitIndex] : false;
                        $bitIndex++;
                    }
                }
            }
        }
    }

    /**
     * XOR non-function modules with mask pattern 0..7.
     *
     * @param  array<int, array<int, bool>>  $modules
     * @param  array<int, array<int, bool>>  $isFunction
     */
    private static function applyMask(array &$modules, array &$isFunction, int $mask): void
    {
        $size = count($modules);
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
                    $modules[$y][$x] = ! $modules[$y][$x];
                }
            }
        }
    }

    /**
     * Compute ISO/IEC 18004 mask penalty score (run lengths, 2x2 blocks, finder-like patterns, dark balance).
     *
     * @param  array<int, array<int, bool>>  $modules
     */
    private static function calculatePenalty(array $modules): int
    {
        $size = count($modules);
        $penalty = 0;

        // N1: Consecutive runs >= 5 in rows and columns
        for ($i = 0; $i < $size; $i++) {
            $rowRun = 1;
            $colRun = 1;
            for ($j = 1; $j < $size; $j++) {
                if ($modules[$i][$j] === $modules[$i][$j - 1]) {
                    $rowRun++;
                    if ($rowRun === 5) {
                        $penalty += 3;
                    } elseif ($rowRun > 5) {
                        $penalty += 1;
                    }
                } else {
                    $rowRun = 1;
                }

                if ($modules[$j][$i] === $modules[$j - 1][$i]) {
                    $colRun++;
                    if ($colRun === 5) {
                        $penalty += 3;
                    } elseif ($colRun > 5) {
                        $penalty += 1;
                    }
                } else {
                    $colRun = 1;
                }
            }
        }

        // N2: 2x2 blocks of identical color
        for ($y = 0; $y < $size - 1; $y++) {
            for ($x = 0; $x < $size - 1; $x++) {
                $c = $modules[$y][$x];
                if ($c === $modules[$y][$x + 1] && $c === $modules[$y + 1][$x] && $c === $modules[$y + 1][$x + 1]) {
                    $penalty += 3;
                }
            }
        }

        // N3: Finder-like 1:1:3:1:1 patterns preceded or followed by 4 light modules
        for ($i = 0; $i < $size; $i++) {
            $rowBits = 0;
            $colBits = 0;
            for ($j = 0; $j < $size; $j++) {
                $rowBits = (($rowBits << 1) & 0x7FF) | ($modules[$i][$j] ? 1 : 0);
                $colBits = (($colBits << 1) & 0x7FF) | ($modules[$j][$i] ? 1 : 0);

                if ($j >= 10) {
                    if ($rowBits === 0x5D0 || $rowBits === 0x05D) {
                        $penalty += 40;
                    }
                    if ($colBits === 0x5D0 || $colBits === 0x05D) {
                        $penalty += 40;
                    }
                }
            }
        }

        // N4: Dark/light module ratio balance
        $dark = 0;
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                if ($modules[$y][$x]) {
                    $dark++;
                }
            }
        }
        $total = $size * $size;
        $k = intdiv(abs(($dark * 20) - ($total * 10)) + $total - 1, $total) - 1;
        if ($k > 0) {
            $penalty += $k * 10;
        }

        return $penalty;
    }
}
