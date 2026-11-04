# CLA58-PHP

A small, dependency-free PHP library for the **CLA58** (a.k.a. `TECH` USB ID `6868:0200`) 58 mm
thermal receipt/label printer. It talks to the printer over `/dev/usb/lp0` using standard
**ESC/POS** commands, so no vendor driver is required.

```
Bus 001 Device 008: ID 6868:0200 TECH CLA58
```

## Hardware notes

The printer enumerates as a standard **USB Printer Class** device (class `7`, subclass `1`,
protocol `2`, "bidirectional"), so the Linux kernel binds it to the `usblp` driver and exposes
it as `/dev/usb/lp0`. Printing is simply writing ESC/POS byte sequences to that character
device; status can be read back from it.

| Property        | Value                                             |
|-----------------|---------------------------------------------------|
| Vendor/Product  | `0x6868` / `0x0200`                               |
| Interface       | USB Printer class, bidirectional                  |
| Endpoints       | Bulk OUT `0x01`, Bulk IN `0x82`                   |
| Resolution      | 203 DPI, 8 dots/mm                                |
| Printable width | 384 dots (58 mm paper)                            |
| Font A          | 12x24 dots → **32 columns**                       |
| Font B          | 9x17 dots → **42 columns**                        |
| Command set     | ESC/POS compatible                                |

### Verified compatibility

Everything below was tested on real hardware:

| Feature | Command | Status |
|---|---|---|
| Text, LF, alignment, bold, underline, double-strike, reverse, font A/B, double size | ESC/POS | ✅ |
| Upside-down, 90° rotation, character spacing, line spacing | ESC/POS | ✅ |
| Code pages **CP437** and **CP850** | `ESC t` | ✅ |
| Barcodes UPC-A, EAN-13, EAN-8, CODE39, ITF, CODABAR (legacy `GS k` types 0-6) | `GS k` | ✅ |
| QR code (models 1 & 2, `GS ( k`) | `GS ( k` | ✅ |
| Raster graphics (`GS v 0`) and bit images (`ESC *`) | | ✅ |
| Real-time / paper status (`DLE EOT`) | `DLE EOT` | ✅ |
| Extended barcodes (`GS k` types 65-73, incl. **CODE93/CODE128**) | `GS k` | ❌ unsupported |
| Code pages CP858 / CP1252 | `ESC t` | ❌ unsupported |
| Horizontal tab stops (`ESC D` + `HT`) | | ⚠️ not honoured |
| Auto-cutter | `GS V` | ⚠️ no cutter in hardware |
| `©`, `®`, `€` glyphs | | ❌ not in the printer font |

> The `cut()` and `cashDrawer()` commands are still emitted (they are valid ESC/POS), but this
> printer has no cutter and no cash-drawer port.

## Requirements

- PHP 8.0+
- Linux with the `usblp` kernel driver (default) and access to `/dev/usb/lp0`
- Optional: `ext-gd` for printing images from PNG/JPEG files
- No Composer packages are required

### Permissions

`/dev/usb/lp0` is owned by `root:lp` with mode `0660`. Add your user to the `lp` group
(then log out/in):

```bash
sudo usermod -aG lp "$USER"
```

## Installation

Copy the `src/` directory into your project and use the bundled autoloader:

```php
require '/path/to/CLA58-PHP/autoload.php';
```

Or, with Composer, point a path repository at this project.

## Quick start

```php
require __DIR__ . '/vendor/autoload.php'; // or ../CLA58-PHP/autoload.php

use Cla58\Printer;

$printer = new Printer('/dev/usb/lp0');

$printer->init();
$printer->align('center')->bold()->size(2)->line('HELLO WORLD');
$printer->size(1)->bold(false);
$printer->align('left')->line('This is a test receipt.');
$printer->twoColumns('Total', '9.99');
$printer->feed(3);

$printer->close();
```

Every fluent method returns `$this`, so calls can be chained.

## Usage

### Connecting

```php
use Cla58\Printer;

// Default device
$printer = new Printer('/dev/usb/lp0');

// File / FIFO (useful for capturing output in tests)
$printer = new Printer('file:///tmp/cla58.out');

// Network printer (Ethernet/Wi-Fi variants, raw port 9100)
$printer = new Printer('tcp://192.168.1.50:9100');

// Custom connection object
$printer = new Printer(new \Cla58\Connection\NetworkConnection('192.168.1.50', 9100));
```

### Text & formatting

```php
$printer->init();                       // ESC @ + reset code page
$printer->text('no newline');
$printer->line('a full line');
$printer->writeWrapped($longText, 32);  // word-wrap to the line width

$printer->align('center');              // 'left' | 'center' | 'right'
$printer->bold(true);
$printer->underline(1);                 // 0, 1 or 2 dots
$printer->doubleStrike(true);
$printer->invert(true);                 // white on black
$printer->font('B');                    // 'A' (32 cols) or 'B' (42 cols)
$printer->size(2, 2);                   // width x height multipliers (1-8)
$printer->upsideDown(true);
$printer->rotate90(true);
$printer->charSpacing(5);
$printer->lineSpacing(40);

$printer->columns();                    // current usable columns
$printer->twoColumns('Item', '1.00');   // left/right justified on one line
$printer->rule('-');                    // full-width divider
```

### Encoding

Text is UTF-8 in, codepage bytes out. The default code page is **CP437** (verified working);
**CP850** is also verified. Unsupported glyphs become `?`.

```php
$printer->codepage('CP437');            // or 'CP850'
$printer->line('ÄÖÜäöüß é è à ñ ç ° ± ½');
```

`©`, `®` and `€` are not present in the printer font on this unit.

### Barcodes

This printer only implements the **legacy** `GS k` barcode command with types `0`-`6`
(UPC-A, UPC-E, EAN-13, EAN-8, CODE39, ITF, CODABAR). CODE93/CODE128 are **not** supported.

```php
$printer->barcode('5901234123457', 'EAN13');
$printer->barcode('CLA58-2024', 'CODE39', height: 70, width: 3);
```

`barcodeExtended()` emits the extended `GS k` (types 65-73) for other printers, but the CLA58
ignores those commands.

### QR code

```php
$printer->qr('https://example.com', moduleSize: 6, errorCorrection: 'M');
```

Module size is 1-16; error correction is `L`, `M`, `Q` or `H`.

### Images

Raster printing (`GS v 0`) works. Build a bitmap directly, or use GD for PNG/JPEG/screenshots.

```php
use Cla58\Raster;

// From a boolean grid (true = black dot)
$rows = [[true, true, false], [false, true, true]];
$printer->raster(Raster::fromBooleans($rows, 3));

// From a file (requires ext-gd)
$printer->imageFromFile('logo.png', maxWidth: 384);

// From a GD resource
$printer->image($gdImage);
```

You can also emit raw Escape sequences with `EscPos`:

```php
use Cla58\EscPos;

$printer->raw(EscPos::raster($width, $height, $bitmap));
```

### Status

```php
$printer->isOnline();          // ?bool
$printer->paperOut();          // ?bool
$printer->status(4);           // ?int raw real-time status byte
```

`null` means the printer did not answer (e.g. the connection is write-only).

### Miscellaneous

```php
$printer->feed(3);             // feed 3 lines
$printer->feedDots(120);       // feed by dots
$printer->beep();              // valid command; may be silent
$printer->cashDrawer(0);       // pulse drawer pin (no port on this unit)
$printer->cut();               // valid command (no cutter on this unit)
$printer->init();              // reset
$printer->test();              // print a self-test page
```

## Command-line tool

```
./bin/cla58 test
./bin/cla58 status
./bin/cla58 text --center --bold "Hello"
./bin/cla58 wrap "long text ..."
./bin/cla58 qr "https://example.com"
./bin/cla58 barcode EAN13 5901234123457
./bin/cla58 image logo.png
./bin/cla58 raw "1b40 48690a"
./bin/cla58 feed 3
./bin/cla58 drawer
```

Set the device with `--device=/dev/usb/lp0` or the `CLA58_DEVICE` environment variable.

## Examples

Runnable scripts in [`examples/`](examples):

| File | Purpose |
|---|---|
| `receipt.php` | Full receipt with totals, barcode and QR |
| `diagnostics.php` | Column-width and feature diagnostics |
| `formatting.php` | Exercises every text-formatting command |
| `encoding.php` | Compares code pages |
| `barcode_types.php` | Prints every supported barcode type |
| `compat.php` / `raster_probe.php` | Probes used during reverse-engineering |

Run any of them with `php examples/receipt.php` (override the device with `CLA58_DEVICE`).

## Testing

A dependency-free test suite verifies command bytes, encoding and layout logic without
requiring a printer:

```bash
php tests/run.php
```

## Troubleshooting

- **`Unable to open printer device`** – check `ls -l /dev/usb/lp0`; you need `lp` group
  membership (or run as `root`).
- **Nothing prints** – confirm the device: `lsusb | grep 6868` and `lsusb -t`.
- **`/dev/usb/lp0` missing** – the `usblp` module must be loaded: `sudo modprobe usblp`.
  If a desktop print system grabbed the printer, `/dev/usb/lp0` may be absent; it will still
  work via the raw USB endpoints, or by removing the CUPS-held device.
- **Printer prints garbage** – another process may be writing to the device, or the code page
  is wrong for the characters being sent.

## License

MIT. See [LICENSE](LICENSE).
