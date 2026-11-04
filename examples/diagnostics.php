<?php

declare(strict_types=1);

require __DIR__ . '/../autoload.php';

require_once __DIR__ . '/_device.php';

use Cla58\Printer;

$printer = new Printer(cla58_device(), ['codepage' => 'CP437']);

$printer->init();

$printer->align('center')->bold()->size(2, 2)->line('CLA58');
$printer->size(1, 1)->bold(false)->line('CLA58 diagnostics');
$printer->align('left');

$printer->rule('=');
$printer->line('COLUMN TEST (count the digits on line 1):');
$printer->line('0123456789' . '0123456789' . '0123456789' . '0123456789' . '0123456789');
$printer->rule('-');

$printer->line('FONT A 32 expected:');
$printer->line(str_repeat('A', 32) . '|32');
$printer->line('FONT B 42 expected:');
$printer->font('B');
$printer->line(str_repeat('B', 42) . '|42');
$printer->font('A');

$printer->line('DOUBLE WIDTH 2x:');
$printer->size(2, 1);
$printer->line(str_repeat('W', 16) . '|16');
$printer->size(1, 1);

$printer->rule('=');
$printer->align('center')->line('barcode CODE39:');
$printer->barcode('CLA58-123456', 'CODE39');
$printer->feed(2);

$printer->align('center')->line('QR code:');
$printer->qr('https://github.com/LukasGessner/CLA58-PHP', 4);
$printer->feed(4);

$printer->close();
echo "Diagnostics page sent.\n";
