<?php

declare(strict_types=1);

namespace Cla58;

final class EscPos
{
    public const ESC = "\x1b";
    public const GS = "\x1d";
    public const FS = "\x1c";
    public const DLE = "\x10";
    public const LF = "\x0a";
    public const CR = "\x0d";
    public const HT = "\x09";
    public const FF = "\x0c";
    public const NUL = "\x00";

    public const ALIGN_LEFT = 0;
    public const ALIGN_CENTER = 1;
    public const ALIGN_RIGHT = 2;

    public const FONT_A = 0;
    public const FONT_B = 1;

    public const BARCODE_UPC_A = 65;
    public const BARCODE_UPC_E = 66;
    public const BARCODE_EAN13 = 67;
    public const BARCODE_EAN8 = 68;
    public const BARCODE_CODE39 = 69;
    public const BARCODE_ITF = 70;
    public const BARCODE_CODABAR = 71;
    public const BARCODE_CODE93 = 72;
    public const BARCODE_CODE128 = 73;

    public const LEGACY_UPC_A = 0;
    public const LEGACY_UPC_E = 1;
    public const LEGACY_EAN13 = 2;
    public const LEGACY_EAN8 = 3;
    public const LEGACY_CODE39 = 4;
    public const LEGACY_ITF = 5;
    public const LEGACY_CODABAR = 6;

    public const HRI_NONE = 0;
    public const HRI_ABOVE = 1;
    public const HRI_BELOW = 2;
    public const HRI_BOTH = 3;

    public const CUT_FULL = 0;
    public const CUT_PARTIAL = 1;

    public static function initialize(): string
    {
        return self::ESC . '@';
    }

    public static function lineFeed(int $lines = 1): string
    {
        return str_repeat(self::LF, max(0, $lines));
    }

    public static function feedDots(int $dots): string
    {
        $dots = max(0, min(255, $dots));

        return self::ESC . 'J' . chr($dots);
    }

    public static function align(int $align): string
    {
        return self::ESC . 'a' . chr($align);
    }

    public static function bold(bool $on): string
    {
        return self::ESC . 'E' . chr($on ? 1 : 0);
    }

    public static function underline(int $mode): string
    {
        return self::ESC . '-' . chr(max(0, min(2, $mode)));
    }

    public static function doubleStrike(bool $on): string
    {
        return self::ESC . 'G' . chr($on ? 1 : 0);
    }

    public static function font(int $font): string
    {
        return self::ESC . 'M' . chr($font);
    }

    public static function size(int $width, ?int $height = null): string
    {
        $height ??= $width;
        $value = ((max(1, min(8, $width)) - 1) << 4) | (max(1, min(8, $height)) - 1);

        return self::GS . '!' . chr($value);
    }

    public static function invert(bool $on): string
    {
        return self::GS . 'B' . chr($on ? 1 : 0);
    }

    public static function upsideDown(bool $on): string
    {
        return self::ESC . '{' . chr($on ? 1 : 0);
    }

    public static function rotate90(bool $on): string
    {
        return self::ESC . 'V' . chr($on ? 1 : 0);
    }

    public static function charSpacing(int $dots): string
    {
        return self::ESC . ' ' . chr(max(0, min(255, $dots)));
    }

    public static function lineSpacing(int $dots): string
    {
        return self::ESC . '3' . chr(max(0, min(255, $dots)));
    }

    public static function defaultLineSpacing(): string
    {
        return self::ESC . '2';
    }

    public static function codePage(int $page): string
    {
        return self::ESC . 't' . chr($page);
    }

    public static function international(string $charset): string
    {
        $map = ['USA' => 0, 'FRANCE' => 1, 'GERMANY' => 2, 'UK' => 3, 'DENMARK' => 4,
            'SWEDEN' => 5, 'ITALY' => 6, 'SPAIN' => 7, 'JAPAN' => 8, 'NORWAY' => 9,
            'DENMARK2' => 10, 'SPAIN2' => 11, 'LATIN_AMERICA' => 12, 'KOREA' => 13,
            'IRELAND' => 14, 'LEGAL' => 64];
        $key = strtoupper(str_replace(['-', ' '], '_', $charset));

        return self::ESC . 'R' . chr($map[$key] ?? 0);
    }

    public static function horizontalTab(): string
    {
        return self::HT;
    }

    public static function setTabPositions(array $columns): string
    {
        $columns = array_values(array_unique(array_map('intval', $columns)));
        sort($columns);
        $data = '';
        foreach ($columns as $column) {
            $data .= chr(max(1, min(255, $column)));
        }

        return self::ESC . 'D' . $data . self::NUL;
    }

    public static function cashDrawer(int $pin = 0, int $onTime = 25, int $offTime = 250): string
    {
        $m = $pin === 1 ? 1 : 0;

        return self::ESC . 'p' . chr($m) . chr($onTime) . chr($offTime);
    }

    public static function cut(int $mode = self::CUT_FULL, int $feed = 0): string
    {
        if ($feed > 0) {
            return self::GS . 'V' . chr(66) . chr(max(0, min(255, $feed)));
        }
        if ($mode === self::CUT_PARTIAL) {
            return self::GS . 'V' . chr(1);
        }

        return self::GS . 'V' . chr(66) . self::NUL;
    }

    public static function beep(int $times = 1, int $duration = 3): string
    {
        $times = max(1, min(9, $times));
        $duration = max(1, min(9, $duration));

        return self::ESC . 'B' . chr($times) . chr($duration);
    }

    public static function barcodeHeight(int $dots): string
    {
        return self::GS . 'h' . chr(max(1, min(255, $dots)));
    }

    public static function barcodeWidth(int $module): string
    {
        return self::GS . 'w' . chr(max(2, min(6, $module)));
    }

    public static function barcodeHri(int $position): string
    {
        return self::GS . 'H' . chr(max(0, min(3, $position)));
    }

    public static function barcodeHriFont(int $font): string
    {
        return self::GS . 'f' . chr($font);
    }

    public static function barcode(int $type, string $data): string
    {
        if ($type >= 65) {
            $length = strlen($data);
            if ($length > 255) {
                throw new PrinterException('Extended barcode data exceeds 255 bytes');
            }

            return self::GS . 'k' . chr($type) . chr($length) . $data;
        }

        return self::GS . 'k' . chr($type) . $data . self::NUL;
    }

    public static function qrCode(
        string $data,
        int $moduleSize = 4,
        int $errorCorrection = 49,
        int $model = 50,
    ): string {
        $result = self::qrModel($model);
        $result .= self::qrModuleSize($moduleSize);
        $result .= self::qrErrorCorrection($errorCorrection);
        $result .= self::qrStore($data);
        $result .= self::qrPrint();

        return $result;
    }

    public static function qrModel(int $model): string
    {
        return self::GS . '(k' . self::u16(4) . '1A' . chr($model) . self::NUL;
    }

    public static function qrModuleSize(int $size): string
    {
        return self::GS . '(k' . self::u16(3) . '1C' . chr(max(1, min(16, $size)));
    }

    public static function qrErrorCorrection(int $level): string
    {
        return self::GS . '(k' . self::u16(3) . '1E' . chr($level);
    }

    public static function qrStore(string $data): string
    {
        $length = strlen($data) + 3;
        if ($length > 0xFFFF) {
            throw new PrinterException('QR data exceeds the maximum storable length');
        }

        return self::GS . '(k' . self::u16($length) . '1P0' . $data;
    }

    public static function qrPrint(): string
    {
        return self::GS . '(k' . self::u16(3) . '1Q0';
    }

    public static function raster(int $widthDots, int $heightDots, string $bitmap): string
    {
        $bytesPerRow = intdiv($widthDots + 7, 8);

        return self::GS . 'v0' . self::NUL
            . chr($bytesPerRow & 0xFF) . chr(($bytesPerRow >> 8) & 0xFF)
            . chr($heightDots & 0xFF) . chr(($heightDots >> 8) & 0xFF)
            . $bitmap;
    }

    public static function realtimeStatus(int $type): string
    {
        return self::DLE . self::EOT . chr($type);
    }

    public static function paperSensorStatus(): string
    {
        return self::ESC . 'v';
    }

    public static function peripheralStatus(): string
    {
        return self::ESC . 'u' . self::NUL;
    }

    public static function u16(int $value): string
    {
        return chr($value & 0xFF) . chr(($value >> 8) & 0xFF);
    }

    private const EOT = "\x04";
}
