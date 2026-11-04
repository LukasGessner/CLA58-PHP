<?php

declare(strict_types=1);

require __DIR__ . '/../autoload.php';

require_once __DIR__ . '/_device.php';

use Cla58\Printer;

$printer = new Printer(cla58_device(), [
    'codepage' => 'CP437',
]);

$printer->init();

$printer->align('center')->bold()->size(2, 2)->line('FANCY COFFEE');
$printer->size(1, 1)->bold(false)->line('123 Roast Street');
$printer->line('Tel: 555-0142');
$printer->rule('-');

$items = [
    ['Espresso', '2.50'],
    ['Cappuccino', '3.20'],
    ['Blueberry Muffin', '2.80'],
    ['Bottled Water', '1.90'],
];

$printer->align('left');
foreach ($items as [$name, $price]) {
    $printer->twoColumns($name, $price);
}
$printer->rule('-');
$printer->twoColumns('Subtotal', '10.40');
$printer->twoColumns('Tax (8%)', '0.83');
$printer->bold()->twoColumns('TOTAL', '11.23')->bold(false);
$printer->rule('=');

$printer->align('center');
$printer->line('Paid with card **** 4821');
$printer->feed(1);
$printer->qr('https://example.com/receipt/4821', 5);
$printer->feed(1);
$printer->line('Thank you, come again!');
$printer->feed(4);

$printer->close();
echo "Receipt sent.\n";
