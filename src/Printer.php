<?php

declare(strict_types=1);

namespace Cla58;

use Cla58\Connection\ConnectionInterface;
use Cla58\Connection\FileConnection;
use Cla58\Connection\NetworkConnection;

class Printer
{
    public const DOT_WIDTH = 384;
    public const FONT_A_CHAR_WIDTH = 12;
    public const FONT_B_CHAR_WIDTH = 9;
    public const COLUMNS_FONT_A = 32;
    public const COLUMNS_FONT_B = 42;

    private const BARCODE_TYPES_LEGACY = [
        'UPCA' => EscPos::LEGACY_UPC_A,
        'UPCE' => EscPos::LEGACY_UPC_E,
        'EAN13' => EscPos::LEGACY_EAN13,
        'EAN8' => EscPos::LEGACY_EAN8,
        'CODE39' => EscPos::LEGACY_CODE39,
        'ITF' => EscPos::LEGACY_ITF,
        'INTERLEAVED2OF5' => EscPos::LEGACY_ITF,
        'CODABAR' => EscPos::LEGACY_CODABAR,
    ];

    private const BARCODE_TYPES_EXTENDED = [
        'UPCA' => EscPos::BARCODE_UPC_A,
        'UPCE' => EscPos::BARCODE_UPC_E,
        'EAN13' => EscPos::BARCODE_EAN13,
        'EAN8' => EscPos::BARCODE_EAN8,
        'CODE39' => EscPos::BARCODE_CODE39,
        'ITF' => EscPos::BARCODE_ITF,
        'INTERLEAVED2OF5' => EscPos::BARCODE_ITF,
        'CODABAR' => EscPos::BARCODE_CODABAR,
        'CODE93' => EscPos::BARCODE_CODE93,
        'CODE128' => EscPos::BARCODE_CODE128,
    ];

    private ConnectionInterface $connection;
    private int $codePage;
    private int $dotWidth;
    private int $font = EscPos::FONT_A;
    private int $sizeWidth = 1;
    private int $sizeHeight = 1;
    private bool $closed = false;

    public function __construct(
        ConnectionInterface|string $connection = '/dev/usb/lp0',
        array $options = [],
    ) {
        if (is_string($connection)) {
            if (str_starts_with($connection, 'tcp://')) {
                $parts = parse_url($connection);
                $connection = new NetworkConnection(
                    $parts['host'] ?? '127.0.0.1',
                    $parts['port'] ?? 9100,
                );
            } elseif (str_starts_with($connection, 'file://')) {
                $connection = new FileConnection(substr($connection, 7));
            } else {
                $connection = new FileConnection($connection);
            }
        }

        $this->connection = $connection;
        $this->codePage = CodePage::resolve($options['codepage'] ?? CodePage::PC437);
        $this->dotWidth = (int) ($options['width'] ?? self::DOT_WIDTH);

        $this->raw(EscPos::codePage($this->codePage));
        if (isset($options['charset'])) {
            $this->raw(EscPos::international((string) $options['charset']));
        }
    }

    public static function open(string $device = '/dev/usb/lp0', array $options = []): self
    {
        return new self($device, $options);
    }

    public function connection(): ConnectionInterface
    {
        return $this->connection;
    }

    public function raw(string $data): self
    {
        $this->connection->write($data);

        return $this;
    }

    public function init(): self
    {
        $this->font = EscPos::FONT_A;
        $this->sizeWidth = 1;
        $this->sizeHeight = 1;

        return $this->raw(EscPos::initialize() . EscPos::codePage($this->codePage));
    }

    public function codepage(int|string $page): self
    {
        $this->codePage = CodePage::resolve($page);

        return $this->raw(EscPos::codePage($this->codePage));
    }

    public function charset(string $charset): self
    {
        return $this->raw(EscPos::international($charset));
    }

    public function text(string $text): self
    {
        return $this->raw(CodePage::encode($text, $this->codePage));
    }

    public function line(string $text = ''): self
    {
        return $this->text($text)->raw(EscPos::LF);
    }

    public function feed(int $lines = 1): self
    {
        return $this->raw(EscPos::lineFeed($lines));
    }

    public function feedDots(int $dots): self
    {
        $remaining = $dots;
        while ($remaining > 255) {
            $this->raw(EscPos::feedDots(255));
            $remaining -= 255;
        }
        if ($remaining > 0) {
            $this->raw(EscPos::feedDots($remaining));
        }

        return $this;
    }

    public function align(int|string $align): self
    {
        if (is_string($align)) {
            $align = match (strtolower($align)) {
                'center', 'centre', 'middle' => EscPos::ALIGN_CENTER,
                'right' => EscPos::ALIGN_RIGHT,
                default => EscPos::ALIGN_LEFT,
            };
        }

        return $this->raw(EscPos::align($align));
    }

    public function bold(bool $on = true): self
    {
        return $this->raw(EscPos::bold($on));
    }

    public function underline(int $mode = 1): self
    {
        return $this->raw(EscPos::underline($mode));
    }

    public function doubleStrike(bool $on = true): self
    {
        return $this->raw(EscPos::doubleStrike($on));
    }

    public function font(string|int $font): self
    {
        if (is_string($font)) {
            $font = strtoupper($font) === 'B' ? EscPos::FONT_B : EscPos::FONT_A;
        }
        $this->font = $font;

        return $this->raw(EscPos::font($font));
    }

    public function size(int $width = 1, ?int $height = null): self
    {
        $this->sizeWidth = max(1, min(8, $width));
        $this->sizeHeight = max(1, min(8, $height ?? $width));

        return $this->raw(EscPos::size($this->sizeWidth, $this->sizeHeight));
    }

    public function invert(bool $on = true): self
    {
        return $this->raw(EscPos::invert($on));
    }

    public function upsideDown(bool $on = true): self
    {
        return $this->raw(EscPos::upsideDown($on));
    }

    public function rotate90(bool $on = true): self
    {
        return $this->raw(EscPos::rotate90($on));
    }

    public function charSpacing(int $dots): self
    {
        return $this->raw(EscPos::charSpacing($dots));
    }

    public function lineSpacing(int $dots): self
    {
        return $this->raw(EscPos::lineSpacing($dots));
    }

    public function resetLineSpacing(): self
    {
        return $this->raw(EscPos::defaultLineSpacing());
    }

    public function tabs(array $columns): self
    {
        return $this->raw(EscPos::setTabPositions($columns));
    }

    public function columns(): int
    {
        $charWidth = $this->font === EscPos::FONT_B
            ? self::FONT_B_CHAR_WIDTH
            : self::FONT_A_CHAR_WIDTH;

        return max(1, intdiv($this->dotWidth, $charWidth * $this->sizeWidth));
    }

    public function wrap(string $text, ?int $columns = null, bool $breakWords = false): array
    {
        $columns ??= $this->columns();
        $lines = [];

        foreach (preg_split('/\r\n|\r|\n/', $text) as $paragraph) {
            array_push($lines, ...$this->wrapParagraph($paragraph, $columns, $breakWords));
        }

        return $lines;
    }

    public function writeWrapped(string $text, ?int $columns = null, bool $breakWords = false): self
    {
        foreach ($this->wrap($text, $columns, $breakWords) as $line) {
            $this->line($line);
        }

        return $this;
    }

    public function twoColumns(string $left, string $right, ?int $columns = null): self
    {
        $columns ??= $this->columns();
        $left = trim($left);
        $right = trim($right);
        $space = $columns - mb_strwidth($right) - mb_strwidth($left);
        if ($space < 1) {
            $this->line($left);
            return $this->line($right);
        }

        return $this->line($left . str_repeat(' ', $space) . $right);
    }

    public function rule(string $char = '-', ?int $columns = null): self
    {
        $columns ??= $this->columns();
        $char = $char === '' ? '-' : mb_substr($char, 0, 1);

        return $this->line(str_repeat($char, $columns));
    }

    public function barcode(
        string $data,
        int|string $type = 'CODE39',
        int $height = 80,
        int $width = 2,
        int $hri = EscPos::HRI_BELOW,
        bool $extended = false,
    ): self {
        $type = $this->resolveBarcodeType($type, $extended);
        $payload = $this->barcodePayload($type, $data, $extended);

        return $this->raw(
            EscPos::barcodeHriFont(EscPos::FONT_A)
            . EscPos::barcodeHeight($height)
            . EscPos::barcodeWidth($width)
            . EscPos::barcodeHri($hri)
            . EscPos::barcode($type, $payload)
        );
    }

    public function barcodeExtended(
        string $data,
        int|string $type = 'CODE128',
        int $height = 80,
        int $width = 2,
        int $hri = EscPos::HRI_BELOW,
    ): self {
        return $this->barcode($data, $type, $height, $width, $hri, true);
    }

    public function qr(string $data, int $moduleSize = 4, int|string $errorCorrection = 'M'): self
    {
        $level = is_string($errorCorrection) ? match (strtoupper($errorCorrection)) {
            'L' => 48,
            'Q' => 50,
            'H' => 51,
            default => 49,
        } : $errorCorrection;

        return $this->raw(EscPos::qrCode($data, $moduleSize, $level));
    }

    public function raster(Raster $raster): self
    {
        return $this->raw(EscPos::raster($raster->width, $raster->height, $raster->data));
    }

    public function imageFromFile(string $path, int $maxWidth = self::DOT_WIDTH, int $threshold = 128): self
    {
        return $this->raster(Raster::fromFile($path, $maxWidth, $threshold));
    }

    public function image(\GdImage $image, int $maxWidth = self::DOT_WIDTH, int $threshold = 128): self
    {
        return $this->raster(Raster::fromGd($image, $maxWidth, $threshold));
    }

    public function cashDrawer(int $pin = 0): self
    {
        return $this->raw(EscPos::cashDrawer($pin));
    }

    public function cut(int $mode = EscPos::CUT_FULL, int $feed = 0): self
    {
        return $this->raw(EscPos::cut($mode, $feed));
    }

    public function beep(int $times = 1, int $duration = 3): self
    {
        return $this->raw(EscPos::beep($times, $duration));
    }

    public function status(int $type = 1, float $timeout = 1.0): ?int
    {
        $this->raw(EscPos::realtimeStatus($type));
        $response = $this->connection->read(2, $timeout);
        if ($response === '') {
            return null;
        }

        return ord($response[strlen($response) - 1]);
    }

    public function paperSensorStatus(float $timeout = 1.0): ?int
    {
        $this->raw(EscPos::paperSensorStatus());
        $response = $this->connection->read(1, $timeout);

        return $response === '' ? null : ord($response[0]);
    }

    public function paperOut(float $timeout = 1.0): ?bool
    {
        $status = $this->status(4, $timeout);
        if ($status === null) {
            return null;
        }

        return (bool) ($status & 0x60);
    }

    public function isOnline(float $timeout = 1.0): ?bool
    {
        $status = $this->status(1, $timeout);
        if ($status === null) {
            return null;
        }

        return ($status & 0x08) === 0;
    }

    public function test(): self
    {
        $this->init();
        $this->align('center')->size(2, 2)->bold()->line('CLA58');
        $this->size(1, 1)->bold(false)->line('ESC/POS THERMAL PRINTER');
        $this->align('left')->rule('=');
        $this->twoColumns('Printer', 'CLA58');
        $this->twoColumns('Interface', 'USB 6868:0200');
        $this->twoColumns('Width', $this->dotWidth . ' dots');
        $this->twoColumns('Columns', (string) $this->columns());
        $this->rule('-');
        $this->line('!"#$%&\'()*+,-./0123456789');
        $this->line('ABCDEFGHIJKLMNOPQRSTUVWXYZ');
        $this->line('abcdefghijklmnopqrstuvwxyz');
        $this->line('ÄÖÜäöüß é è à ñ ç ° ± ½');
        $this->rule('=');
        $this->align('center')->line('Barcode (CODE39):');
        $this->barcode('CLA58-2024', 'CODE39', 70, 3);
        $this->feed(1);
        $this->line('QR code:');
        $this->qr('https://github.com/LukasGessner/CLA58-PHP', 6);
        $this->feed(2);
        $this->align('center')->line('Thank you!');
        $this->feed(3);

        return $this;
    }

    public function isOpen(): bool
    {
        return !$this->closed && $this->connection->isOpen();
    }

    public function close(): void
    {
        if (!$this->closed) {
            $this->connection->close();
            $this->closed = true;
        }
    }

    public function __destruct()
    {
        $this->close();
    }

    private function resolveBarcodeType(int|string $type, bool $extended): int
    {
        if (is_int($type)) {
            return $type;
        }

        $key = strtoupper(str_replace(['-', '_', ' '], '', $type));
        $table = $extended ? self::BARCODE_TYPES_EXTENDED : self::BARCODE_TYPES_LEGACY;

        if (!isset($table[$key])) {
            $hint = isset(self::BARCODE_TYPES_EXTENDED[$key])
                ? ' This printer only supports UPC-A, UPC-E, EAN-13, EAN-8, CODE39, ITF and CODABAR via the legacy command.'
                : '';
            throw new PrinterException(sprintf('Unknown or unsupported barcode type "%s".%s', $type, $hint));
        }

        return $table[$key];
    }

    private function barcodePayload(int $type, string $data, bool $extended): string
    {
        if ($extended && $type === EscPos::BARCODE_CODE128 && !preg_match('/^\{[ABC]/', $data)) {
            $data = '{B' . $data;
        }

        return CodePage::encode($data, $this->codePage);
    }

    private function wrapParagraph(string $paragraph, int $columns, bool $breakWords): array
    {
        if (trim($paragraph) === '') {
            return [''];
        }

        $words = preg_split('/\s+/', trim($paragraph)) ?: [];
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            if ($current !== '' && mb_strwidth($current . ' ' . $word, 'UTF-8') <= $columns) {
                $current .= ' ' . $word;
                continue;
            }

            if ($current !== '') {
                $lines[] = $current;
                $current = '';
            }

            if (mb_strwidth($word, 'UTF-8') <= $columns) {
                $current = $word;
                continue;
            }

            if ($breakWords) {
                foreach ($this->splitByWidth($word, $columns) as $chunk) {
                    $lines[] = $chunk;
                }
            } else {
                $current = $word;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines === [] ? [''] : $lines;
    }

    private function splitByWidth(string $text, int $columns): array
    {
        $chunks = [];
        $current = '';
        $width = 0;

        foreach (mb_str_split($text) as $char) {
            $charWidth = mb_strwidth($char, 'UTF-8');
            if ($current !== '' && $width + $charWidth > $columns) {
                $chunks[] = $current;
                $current = '';
                $width = 0;
            }
            $current .= $char;
            $width += $charWidth;
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        return $chunks;
    }
}
