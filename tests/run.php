<?php

declare(strict_types=1);

require __DIR__ . '/../autoload.php';

use Cla58\CodePage;
use Cla58\EscPos;
use Cla58\Printer;
use Cla58\PrinterException;
use Cla58\Raster;

$tests = 0;
$failures = 0;

function check(string $name, bool $condition): void
{
    global $tests, $failures;
    $tests++;
    if ($condition) {
        echo "  ok  - {$name}\n";
    } else {
        $failures++;
        echo "FAIL  - {$name}\n";
    }
}

function capture(callable $callback): string
{
    $path = tempnam(sys_get_temp_dir(), 'cla58');
    $printer = new Printer('file://' . $path, ['codepage' => 'CP437']);
    $callback($printer);
    $printer->close();
    $bytes = (string) file_get_contents($path);
    unlink($path);

    return $bytes;
}

echo "CodePage\n";
check('ASCII passthrough', CodePage::encode('Hello') === 'Hello');
check('CP437 umlauts', bin2hex(CodePage::encode('ÄÖÜ', 'CP437')) === '8e999a');
check('CP437 sharp s', CodePage::encode('ß', 'CP437') === "\xe1");
check('CP850 umlauts', bin2hex(CodePage::encode('ÄÖÜ', 'CP850')) === '8e999a');
check('CP850 slash-o', CodePage::encode('ø', 'CP850') === "\x9b");
check('CP858 euro', CodePage::encode('€', 'CP858') === "\xd5");
check('CP1252 euro', CodePage::encode('€', 'CP1252') === "\x80");
check('unknown char becomes ?', CodePage::encode('€', 'CP437') === '?');
check('invalid utf8 becomes ?', CodePage::encode("\xff", 'CP437') === '?');
check('resolve by name', CodePage::resolve('cp850') === CodePage::PC850);

$unsupported = false;
try {
    CodePage::resolve('CP860');
} catch (PrinterException) {
    $unsupported = true;
}
check('unsupported code page rejected', $unsupported);

echo "EscPos\n";
check('init command', EscPos::initialize() === "\x1b@");
check('bold on', EscPos::bold(true) === "\x1b\x45\x01");
check('size 2x2', EscPos::size(2, 2) === "\x1d!\x11");
check('align center', EscPos::align(EscPos::ALIGN_CENTER) === "\x1b\x61\x01");
check('legacy barcode terminator', EscPos::barcode(4, 'ABC') === "\x1d\x6b\x04ABC\x00");
check('extended barcode length byte', EscPos::barcode(73, 'ABC') === "\x1d\x6b\x49\x03ABC");
check('euro-capable codepage cmd', EscPos::codePage(19) === "\x1bt\x13");

$qrOverflow = false;
try {
    EscPos::qrStore(str_repeat('a', 0x10000));
} catch (PrinterException) {
    $qrOverflow = true;
}
check('oversized QR payload rejected', $qrOverflow);

echo "Printer output\n";
$pre = "\x1bt\x00";
check('init writes ESC @ and ESC t 0', capture(fn (Printer $p) => $p->init()) === $pre . "\x1b@\x1bt\x00");
check('line appends LF', capture(fn (Printer $p) => $p->line('hi')) === $pre . "hi\x0a");
check('text is codepage encoded', capture(fn (Printer $p) => $p->text('Ä')) === $pre . "\x8e");
check('feed 3', capture(fn (Printer $p) => $p->feed(3)) === $pre . "\x0a\x0a\x0a");
check('underline', capture(fn (Printer $p) => $p->underline(2)) === $pre . "\x1b-\x02");
check('raster header', str_starts_with(capture(fn (Printer $p) => $p->raster(Raster::fromBooleans([[true]]))), $pre . "\x1dv0\x00"));

echo "Barcode selection\n";
$rejected = false;
$rejectPath = tempnam(sys_get_temp_dir(), 'cla58');
$probe = new Printer('file://' . $rejectPath);
try {
    $probe->barcode('x', 'CODE128', extended: false);
} catch (PrinterException) {
    $rejected = true;
} finally {
    $probe->close();
    unlink($rejectPath);
}
check('CODE128 rejected without --extended', $rejected);

$legacy = capture(fn (Printer $p) => $p->barcode('123456', 'EAN13'));
check('EAN13 uses legacy command 2', str_contains($legacy, "\x1d\x6b\x02"));

$extended = capture(fn (Printer $p) => $p->barcodeExtended('123', 'CODE128'));
check('extended CODE128 uses command 73', str_contains($extended, "\x1d\x6b\x49"));
check('extended CODE128 has length byte, no NUL', str_contains($extended, "\x1d\x6b\x49\x05{B123"));

echo "Layout\n";
$layoutPath = tempnam(sys_get_temp_dir(), 'cla58');
$layout = new Printer('file://' . $layoutPath);
check('font A columns = 32', $layout->columns() === 32);
$layout->size(2);
check('double width columns = 16', $layout->columns() === 16);
$layout->font('B');
$layout->size(1);
check('font B columns = 42', $layout->columns() === 42);
$wrapped = $layout->wrap('The quick brown fox jumps over the lazy dog', 16);
check('wrap respects width', max(array_map('mb_strwidth', $wrapped)) <= 16);
check('wrap keeps all words', implode(' ', $wrapped) === 'The quick brown fox jumps over the lazy dog');
$layout->close();
unlink($layoutPath);

$twoColumns = capture(fn (Printer $p) => $p->twoColumns('Total', '9.99'));
check('twoColumns right-aligns value', $twoColumns === $pre . 'Total' . str_repeat(' ', 23) . "9.99\x0a");

echo "\n{$tests} tests, {$failures} failures\n";
exit($failures === 0 ? 0 : 1);
