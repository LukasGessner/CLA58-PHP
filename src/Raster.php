<?php

declare(strict_types=1);

namespace Cla58;

final class Raster
{
    public function __construct(
        public readonly int $width,
        public readonly int $height,
        public readonly string $data,
    ) {
    }

    public static function fromBooleans(array $rows, ?int $width = null): self
    {
        $height = count($rows);
        $width ??= self::maxRowLength($rows);
        if ($width <= 0) {
            throw new PrinterException('Raster width must be greater than zero');
        }

        $data = '';

        foreach ($rows as $row) {
            $bits = array_values($row);
            for ($x = 0; $x < $width; $x += 8) {
                $byte = 0;
                for ($bit = 0; $bit < 8; $bit++) {
                    $index = $x + $bit;
                    if ($index < $width && !empty($bits[$index])) {
                        $byte |= 0x80 >> $bit;
                    }
                }
                $data .= chr($byte);
            }
        }

        return new self($width, $height, $data);
    }

    public static function fromGd(\GdImage $image, int $maxWidth = 384, int $threshold = 128): self
    {
        $sourceWidth = imagesx($image);
        $sourceHeight = imagesy($image);
        if ($sourceWidth <= 0 || $sourceHeight <= 0) {
            throw new PrinterException('Invalid image dimensions');
        }

        $scale = min(1.0, $maxWidth / $sourceWidth);
        $targetWidth = max(1, (int) round($sourceWidth * $scale));
        $targetHeight = max(1, (int) round($sourceHeight * $scale));

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        imagecopyresampled(
            $canvas,
            $image,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $sourceWidth,
            $sourceHeight
        );

        $data = '';

        for ($y = 0; $y < $targetHeight; $y++) {
            for ($x = 0; $x < $targetWidth; $x += 8) {
                $byte = 0;
                for ($bit = 0; $bit < 8; $bit++) {
                    $sampleX = $x + $bit;
                    if ($sampleX >= $targetWidth) {
                        break;
                    }
                    $rgb = imagecolorat($canvas, $sampleX, $y);
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;
                    $luminance = (int) round(0.299 * $r + 0.587 * $g + 0.114 * $b);
                    if ($luminance < $threshold) {
                        $byte |= 0x80 >> $bit;
                    }
                }
                $data .= chr($byte);
            }
        }

        imagedestroy($canvas);

        return new self($targetWidth, $targetHeight, $data);
    }

    public static function fromFile(string $path, int $maxWidth = 384, int $threshold = 128): self
    {
        if (!extension_loaded('gd')) {
            throw new PrinterException('The GD extension is required to load images from files');
        }
        if (!is_file($path)) {
            throw new PrinterException(sprintf('Image file not found: %s', $path));
        }

        $contents = @file_get_contents($path);
        if ($contents === false) {
            throw new PrinterException(sprintf('Unable to read image file: %s', $path));
        }

        $image = @imagecreatefromstring($contents);
        if ($image === false) {
            throw new PrinterException(sprintf('Unsupported image format: %s', $path));
        }

        try {
            return self::fromGd($image, $maxWidth, $threshold);
        } finally {
            imagedestroy($image);
        }
    }

    public function bytesPerRow(): int
    {
        return intdiv($this->width + 7, 8);
    }

    private static function maxRowLength(array $rows): int
    {
        $max = 0;
        foreach ($rows as $row) {
            $max = max($max, count($row));
        }

        return $max;
    }
}
