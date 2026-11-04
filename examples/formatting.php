<?php

declare(strict_types=1);

require __DIR__ . '/../autoload.php';

require_once __DIR__ . '/_device.php';

use Cla58\Printer;

$printer = new Printer(cla58_device(), ['codepage' => 'CP437']);
$printer->init()->align('left');

$printer->line('1 normal: Highly charged');
$printer->bold()->line('1 bold:   Highly charged')->bold(false);

$printer->underline(1)->line('2 underline 1 dot')->underline(0);
$printer->underline(2)->line('2 underline 2 dots')->underline(0);

$printer->doubleStrike()->line('3 double strike')->doubleStrike(false);

$printer->invert()->line('4 reverse / inverted')->invert(false);

$printer->font('B')->line('5 font B (42 columns)')->font('A');

$printer->upsideDown()->line('6 upside down')->upsideDown(false);

$printer->rotate90()->line('7 rotate 90')->rotate90(false);

$printer->charSpacing(5)->line('8 char spacing +5')->charSpacing(0);

$printer->align('right')->line('9 align right')->align('left');

$printer->line('10 codepages (same 0xD5 byte):');
$printer->codepage('CP437')->text("  CP437 0xD5 -> \xd5\n");
$printer->codepage('CP850')->text("  CP850 0xD5 -> \xd5\n");
$printer->codepage('CP858')->text("  CP858 0xD5 -> \xd5\n");
$printer->codepage('CP1252')->text("  CP1252 0x80 -> \x80\n");
$printer->codepage('CP437')->text("  CP437 umlauts -> \x8e\x99\x9a\x84\x94\x81\xe1\n");

$printer->line('11 tabs:');
$printer->tabs([8, 16, 24]);
$printer->line("col0\tcol1\tcol2\tcol3");

$printer->line('12 beep (may be silent)');
$printer->beep();

$printer->align('center')->line('-- END --');
$printer->feed(4);

$printer->close();
echo "Formatting page sent.\n";
