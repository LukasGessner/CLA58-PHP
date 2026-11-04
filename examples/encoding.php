<?php

declare(strict_types=1);

require __DIR__ . '/../autoload.php';

require_once __DIR__ . '/_device.php';

use Cla58\CodePage;
use Cla58\Printer;

$sample = 'ÄÖÜäöüß é è à ñ ç ° ± ½ © ® € £';

$printer = new Printer(cla58_device(), ['codepage' => 'CP437']);
$printer->init()->align('left');

$printer->bold()->line('A: raw UTF-8 passthrough')->bold(false);
$printer->raw($sample . "\n\n");

$printer->bold()->line('B: CP437 bytes')->bold(false);
$printer->raw(CodePage::encode($sample, 'CP437') . "\n\n");

$printer->bold()->line('C: CP850 bytes')->bold(false);
$printer->raw(CodePage::encode($sample, 'CP850') . "\n\n");

$printer->bold()->line('D: CP858 bytes')->bold(false);
$printer->raw(CodePage::encode($sample, 'CP858') . "\n\n");

$printer->bold()->line('E: CP1252 bytes')->bold(false);
$printer->raw(CodePage::encode($sample, 'CP1252') . "\n\n");

$printer->bold()->line('F: UTF-8 with ESC t 0 (PC437) set')->bold(false);
$printer->raw(Cla58\EscPos::codePage(0) . $sample . "\n\n");

$printer->bold()->line('END')->bold(false);
$printer->feed(3);

$printer->close();
echo "Encoding page sent.\n";
