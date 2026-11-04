<?php

declare(strict_types=1);

namespace Cla58\Connection;

use Cla58\PrinterException;

final class FileConnection implements ConnectionInterface
{
    private mixed $handle = null;

    public function __construct(
        private string $path,
        private float $writeTimeout = 10.0,
    ) {
        $handle = @fopen($path, 'r+b');
        if ($handle === false) {
            throw new PrinterException(sprintf('Unable to open printer device "%s"', $path));
        }
        stream_set_blocking($handle, false);
        $this->handle = $handle;
    }

    public function write(string $data): int
    {
        $handle = $this->handle();
        $total = 0;
        $length = strlen($data);
        $deadline = microtime(true) + $this->writeTimeout;

        while ($total < $length) {
            $written = @fwrite($handle, substr($data, $total, 8192));
            if ($written === false || $written === 0) {
                if (microtime(true) >= $deadline) {
                    throw new PrinterException(sprintf('Timed out writing to "%s"', $this->path));
                }
                usleep(2000);
                continue;
            }
            $total += $written;
        }

        fflush($handle);

        return $total;
    }

    public function read(int $length = 64, float $timeout = 1.0): string
    {
        $handle = $this->handle();
        $result = '';
        $deadline = microtime(true) + $timeout;

        do {
            $remaining = max(0.0, $deadline - microtime(true));
            $read = [$handle];
            $write = null;
            $except = null;

            if (@stream_select($read, $write, $except, 0, (int) ($remaining * 1_000_000)) > 0) {
                $chunk = fread($handle, $length - strlen($result));
                if ($chunk === false || $chunk === '') {
                    break;
                }
                $result .= $chunk;
            } else {
                break;
            }
        } while (strlen($result) < $length && microtime(true) < $deadline);

        return $result;
    }

    public function isOpen(): bool
    {
        return $this->handle !== null && is_resource($this->handle);
    }

    public function close(): void
    {
        if ($this->isOpen()) {
            @fclose($this->handle);
        }
        $this->handle = null;
    }

    public function describe(): string
    {
        return 'device ' . $this->path;
    }

    public function path(): string
    {
        return $this->path;
    }

    private function handle(): mixed
    {
        if (!$this->isOpen()) {
            throw new PrinterException('Connection is not open');
        }

        return $this->handle;
    }
}
