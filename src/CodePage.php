<?php

declare(strict_types=1);

namespace Cla58;

final class CodePage
{
    public const PC437 = 0;
    public const PC850 = 2;
    public const WPC1252 = 16;
    public const PC858 = 19;

    private const SUPPORTED = [self::PC437, self::PC850, self::PC858, self::WPC1252];

    private const NAMES = [
        'CP437' => self::PC437,
        'PC437' => self::PC437,
        '437' => self::PC437,
        'CP850' => self::PC850,
        'PC850' => self::PC850,
        '850' => self::PC850,
        'CP1252' => self::WPC1252,
        'WPC1252' => self::WPC1252,
        '1252' => self::WPC1252,
        'LATIN1' => self::WPC1252,
        'CP858' => self::PC858,
        'PC858' => self::PC858,
        '858' => self::PC858,
        'EURO' => self::PC858,
    ];

    private const CP437_HIGH = [
        0x00C7, 0x00FC, 0x00E9, 0x00E2, 0x00E4, 0x00E0, 0x00E5, 0x00E7,
        0x00EA, 0x00EB, 0x00E8, 0x00EF, 0x00EE, 0x00EC, 0x00C4, 0x00C5,
        0x00C9, 0x00E6, 0x00C6, 0x00F4, 0x00F6, 0x00F2, 0x00FB, 0x00F9,
        0x00FF, 0x00D6, 0x00DC, 0x00A2, 0x00A3, 0x00A5, 0x20A7, 0x0192,
        0x00E1, 0x00ED, 0x00F3, 0x00FA, 0x00F1, 0x00D1, 0x00AA, 0x00BA,
        0x00BF, 0x2310, 0x00AC, 0x00BD, 0x00BC, 0x00A1, 0x00AB, 0x00BB,
        0x2591, 0x2592, 0x2593, 0x2502, 0x2524, 0x2561, 0x2562, 0x2556,
        0x2555, 0x2563, 0x2551, 0x2557, 0x255D, 0x255C, 0x255B, 0x2510,
        0x2514, 0x2534, 0x252C, 0x251C, 0x2500, 0x253C, 0x255E, 0x255F,
        0x255A, 0x2554, 0x2569, 0x2566, 0x2560, 0x2550, 0x256C, 0x2567,
        0x2568, 0x2564, 0x2565, 0x2559, 0x2558, 0x2552, 0x2553, 0x256B,
        0x256A, 0x2518, 0x250C, 0x2588, 0x2584, 0x258C, 0x2590, 0x2580,
        0x03B1, 0x00DF, 0x0393, 0x03C0, 0x03A3, 0x03C3, 0x00B5, 0x03C4,
        0x03A6, 0x0398, 0x03A9, 0x03B4, 0x221E, 0x03C6, 0x03B5, 0x2229,
        0x2261, 0x00B1, 0x2265, 0x2264, 0x2320, 0x2321, 0x00F7, 0x2248,
        0x00B0, 0x2219, 0x00B7, 0x221A, 0x207F, 0x00B2, 0x25A0, 0x00A0,
    ];

    private const CP850_HIGH = [
        0x00C7, 0x00FC, 0x00E9, 0x00E2, 0x00E4, 0x00E0, 0x00E5, 0x00E7,
        0x00EA, 0x00EB, 0x00E8, 0x00EF, 0x00EE, 0x00EC, 0x00C4, 0x00C5,
        0x00C9, 0x00E6, 0x00C6, 0x00F4, 0x00F6, 0x00F2, 0x00FB, 0x00F9,
        0x00FF, 0x00D6, 0x00DC, 0x00F8, 0x00A3, 0x00D8, 0x00D7, 0x0192,
        0x00E1, 0x00ED, 0x00F3, 0x00FA, 0x00F1, 0x00D1, 0x00AA, 0x00BA,
        0x00BF, 0x00AE, 0x00AC, 0x00BD, 0x00BC, 0x00A1, 0x00AB, 0x00BB,
        0x2591, 0x2592, 0x2593, 0x2502, 0x2524, 0x00C1, 0x00C2, 0x00C0,
        0x00A9, 0x2563, 0x2551, 0x2557, 0x255D, 0x00A2, 0x00A5, 0x2510,
        0x2514, 0x2534, 0x252C, 0x251C, 0x2500, 0x253C, 0x00E3, 0x00C3,
        0x255A, 0x2554, 0x2569, 0x2566, 0x2560, 0x2550, 0x256C, 0x00A4,
        0x00F0, 0x00D0, 0x00CA, 0x00CB, 0x00C8, 0x0131, 0x00CD, 0x00CE,
        0x00CF, 0x2518, 0x250C, 0x2588, 0x2584, 0x00A6, 0x00CC, 0x2580,
        0x00D3, 0x00DF, 0x00D4, 0x00D2, 0x00F5, 0x00D5, 0x00B5, 0x00FE,
        0x00DE, 0x00DA, 0x00DB, 0x00D9, 0x00FD, 0x00DD, 0x00AF, 0x00B4,
        0x00AD, 0x00B1, 0x2017, 0x00BE, 0x00B6, 0x00A7, 0x00F7, 0x00B8,
        0x00B0, 0x00A8, 0x00B7, 0x00B9, 0x00B3, 0x00B2, 0x25A0, 0x00A0,
    ];

    private const CP1252_HIGH = [
        0x20AC, 0x0081, 0x201A, 0x0192, 0x201E, 0x2026, 0x2020, 0x2021,
        0x02C6, 0x2030, 0x0160, 0x2039, 0x0152, 0x008D, 0x017D, 0x008F,
        0x0090, 0x2018, 0x2019, 0x201C, 0x201D, 0x2022, 0x2013, 0x2014,
        0x02DC, 0x2122, 0x0161, 0x203A, 0x0153, 0x009D, 0x017E, 0x0178,
    ];

    public static function resolve(int|string $page): int
    {
        if (is_int($page)) {
            $resolved = $page;
        } else {
            $key = strtoupper(str_replace(['-', '_', ' '], '', $page));
            if (!isset(self::NAMES[$key])) {
                throw new PrinterException(sprintf('Unknown code page "%s"', $page));
            }
            $resolved = self::NAMES[$key];
        }

        if (!in_array($resolved, self::SUPPORTED, true)) {
            throw new PrinterException(sprintf('Unsupported code page %d', $resolved));
        }

        return $resolved;
    }

    public static function encode(string $utf8, int|string $page = self::PC437): string
    {
        $page = self::resolve($page);
        $map = self::map($page);
        $result = '';
        $length = strlen($utf8);

        for ($i = 0; $i < $length;) {
            $code = self::nextCodePoint($utf8, $i);

            if ($code === -1) {
                $result .= '?';
                continue;
            }

            $result .= $map[$code] ?? '?';
        }

        return $result;
    }

    private static function map(int $page): array
    {
        static $cache = [];

        if (isset($cache[$page])) {
            return $cache[$page];
        }

        $map = [];
        for ($byte = 0x20; $byte <= 0x7E; $byte++) {
            $map[$byte] = chr($byte);
        }

        $high = match ($page) {
            self::PC850 => self::CP850_HIGH,
            self::PC858 => array_replace(self::CP850_HIGH, [0x55 => 0x20AC]),
            self::WPC1252 => array_merge(self::CP1252_HIGH, range(0x00A0, 0x00FF)),
            self::PC437 => self::CP437_HIGH,
            default => throw new PrinterException(sprintf('Unsupported code page %d', $page)),
        };

        foreach ($high as $offset => $codePoint) {
            $map[$codePoint] = chr(0x80 + $offset);
        }

        return $cache[$page] = $map;
    }

    private static function nextCodePoint(string $string, int &$offset): int
    {
        $first = ord($string[$offset]);

        if ($first < 0x80) {
            $offset++;

            return $first;
        }

        if (($first & 0xE0) === 0xC0) {
            $needed = 1;
            $code = $first & 0x1F;
        } elseif (($first & 0xF0) === 0xE0) {
            $needed = 2;
            $code = $first & 0x0F;
        } elseif (($first & 0xF8) === 0xF0) {
            $needed = 3;
            $code = $first & 0x07;
        } else {
            $offset++;

            return -1;
        }

        if ($offset + $needed >= strlen($string)) {
            $offset++;

            return -1;
        }

        for ($i = 1; $i <= $needed; $i++) {
            $next = ord($string[$offset + $i]);
            if (($next & 0xC0) !== 0x80) {
                $offset++;

                return -1;
            }
            $code = ($code << 6) | ($next & 0x3F);
        }

        $offset += $needed + 1;

        return $code;
    }
}
