<?php

declare(strict_types=1);

require __DIR__ . '/../autoload.php';

require_once __DIR__ . '/_device.php';

use Cla58\EscPos;
use Cla58\Printer;
use Cla58\Raster;

$printer = new Printer(cla58_device(), ['codepage' => 'CP437']);
$printer->init();

$printer->align('left')->bold()->line('1. RASTER GS v 0 (200x48)')->bold(false);
$rows = [];
for ($y = 0; $y < 48; $y++) {
    $row = [];
    for ($x = 0; $x < 200; $x++) {
        if ($y < 12) {
            $row[] = true;
        } elseif ($y < 24) {
            $row[] = (intdiv($x, 10) % 2) === 0;
        } elseif ($y < 36) {
            $row[] = $x < 100;
        } else {
            $row[] = ($x % 20) < 10 && ($y % 8) < 4;
        }
    }
    $rows[] = $row;
}
$printer->raster(Raster::fromBooleans($rows, 200));
$printer->feed(3);

$printer->bold()->line('2. BIT IMAGE ESC * m=0 (200x24)')->bold(false);
$width = 200;
$bytesPerRow = intdiv($width + 7, 8);
$image = '';
for ($y = 0; $y < 24; $y++) {
    for ($bx = 0; $bx < $bytesPerRow; $bx++) {
        $byte = 0;
        for ($bit = 0; $bit < 8; $bit++) {
            $x = $bx * 8 + $bit;
            if ($x < $width && (($y % 8) < 4)) {
                $byte |= 0x80 >> $bit;
            }
        }
        $image .= chr($byte);
    }
}
$printer->raw(EscPos::ESC . '*' . chr(0) . chr($bytesPerRow & 0xFF) . chr(($bytesPerRow >> 8) & 0xFF) . $image);
$printer->feed(3);

$printer->bold()->line('3. BARCODE legacy GS k type=4 (CODE39)')->bold(false);
$printer->raw(EscPos::barcodeWidth(3) . EscPos::barcodeHeight(80) . EscPos::barcodeHri(2));
$printer->raw(EscPos::GS . 'k' . chr(4) . 'CLA58TEST' . EscPos::NUL);
$printer->feed(3);

$printer->bold()->line('4. BARCODE extended GS k type=69 (CODE39)')->bold(false);
$printer->raw(EscPos::barcode(69, 'CLA58TEST'));
$printer->feed(3);

$printer->bold()->line('5. BARCODE EAN13 extended type=67')->bold(false);
$printer->raw(EscPos::barcode(67, '5901234123457'));
$printer->feed(3);

$printer->bold()->line('6. QR GS ( k model2')->bold(false);
$printer->raw(EscPos::qrCode('HELLO CLA58', 6, 49, 50));
$printer->feed(3);

$printer->bold()->line('7. QR GS ( k model1')->bold(false);
$printer->raw(EscPos::qrCode('HELLO CLA58', 6, 49, 49));
$printer->feed(3);

$printer->bold()->line('END')->bold(false);
$printer->feed(4);

$printer->close();
echo "Compatibility page sent.\n";
