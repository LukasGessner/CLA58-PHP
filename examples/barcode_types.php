<?php

declare(strict_types=1);

require __DIR__ . '/../autoload.php';

require_once __DIR__ . '/_device.php';

use Cla58\Printer;

$printer = new Printer(cla58_device(), ['codepage' => 'CP437']);
$printer->init()->align('center');

$tests = [
    ['EAN13', '5901234123457'],
    ['EAN8', '96385074'],
    ['UPCA', '123456789012'],
    ['UPCE', '01234565'],
    ['CODE39', 'CLA58 TEST'],
    ['ITF', '1234567890'],
    ['CODABAR', 'A1234567890B'],
];

foreach ($tests as [$type, $data]) {
    $printer->bold()->line($type)->bold(false);
    try {
        $printer->barcode($data, $type, 70, 3);
    } catch (\Throwable $e) {
        $printer->line('ERROR: ' . $e->getMessage());
    }
    $printer->feed(3);
}

$printer->line('QR (size 8):');
$printer->qr('https://github.com/LukasGessner/CLA58-PHP', 8);
$printer->feed(4);

$printer->line('-- END --')->feed(3);
$printer->close();
echo "Barcode types page sent.\n";
