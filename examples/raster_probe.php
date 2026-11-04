<?php

declare(strict_types=1);

require __DIR__ . '/../autoload.php';

require_once __DIR__ . '/_device.php';

use Cla58\EscPos;
use Cla58\Printer;
use Cla58\Raster;

$printer = new Printer(cla58_device(), ['codepage' => 'CP437']);
$printer->init();
$conn = $printer->connection();

function solid(int $width, int $height, bool $on = true): Raster
{
    $rows = [];
    for ($y = 0; $y < $height; $y++) {
        $rows[] = array_fill(0, $width, $on);
    }

    return Raster::fromBooleans($rows, $width);
}

$printer->align('left')->bold()->line('A: GS v 0 384x24 SOLID BLACK BAR')->bold(false);
$printer->raster(solid(384, 24));
$printer->feed(3);

$printer->bold()->line('B: GS v 0 384x24 VERTICAL STRIPES 8px')->bold(false);
$rows = [];
for ($y = 0; $y < 24; $y++) {
    $row = [];
    for ($x = 0; $x < 384; $x++) {
        $row[] = (intdiv($x, 8) % 2) === 0;
    }
    $rows[] = $row;
}
$printer->raster(Raster::fromBooleans($rows, 384));
$printer->feed(3);

$printer->bold()->line('C: GS v 0 384x24 LEFT HALF BLACK')->bold(false);
$rows = [];
for ($y = 0; $y < 24; $y++) {
    $row = [];
    for ($x = 0; $x < 384; $x++) {
        $row[] = $x < 192;
    }
    $rows[] = $row;
}
$printer->raster(Raster::fromBooleans($rows, 384));
$printer->feed(3);

$printer->bold()->line('D: ESC * m=0 384x24 SOLID')->bold(false);
$bytesPerRow = 48;
$image = str_repeat("\xff", $bytesPerRow * 24);
$printer->raw(EscPos::ESC . '*' . chr(0) . chr($bytesPerRow & 0xFF) . chr(($bytesPerRow >> 8) & 0xFF) . $image);
$printer->feed(3);

$printer->bold()->line('E: GS v 0 384x24 SOLID, SLOW CHUNKS')->bold(false);
$raster = solid(384, 24);
$header = EscPos::GS . 'v0' . EscPos::NUL
    . chr($raster->bytesPerRow()) . chr(0)
    . chr($raster->height) . chr(0);
$conn->write($header);
$chunk = 48;
for ($offset = 0; $offset < strlen($raster->data); $offset += $chunk) {
    $conn->write(substr($raster->data, $offset, $chunk));
    usleep(5000);
}
$printer->feed(3);

$printer->bold()->line('END')->bold(false);
$printer->feed(4);

$printer->close();
echo "Raster probe sent.\n";
