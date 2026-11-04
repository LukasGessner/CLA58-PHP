<?php

declare(strict_types=1);

namespace Cla58\Connection;

use Cla58\PrinterException;

final class NetworkConnection implements ConnectionInterface
{
    private mixed $stream = null;

    public function __construct(
        private string $host,
        private int $port = 9100,
        private float $connectTimeout = 3.0,
        private float $writeTimeout = 10.0,
    ) {
    }

    public function write(string $data): int
    {
        $stream = $this->stream();
        $total = 0;
        $length = strlen($data);
        $deadline = microtime(true) + $this->writeTimeout;

        while ($total < $length) {
            $written = @fwrite($stream, substr($data, $total, 8192));
            if ($written === false || $written === 0) {
                if (microtime(true) >= $deadline) {
                    throw new PrinterException(sprintf(
                        'Timed out writing to %s:%d',
                        $this->host,
                        $this->port
                    ));
                }
                usleep(2000);
                continue;
            }
            $total += $written;
        }

        return $total;
    }

    public function read(int $length = 64, float $timeout = 1.0): string
    {
        $stream = $this->stream();
        $read = [$stream];
        $write = null;
        $except = null;

        if (@stream_select($read, $write, $except, 0, (int) ($timeout * 1_000_000)) <= 0) {
            return '';
        }

        $data = @fread($stream, $length);

        return $data === false ? '' : $data;
    }

    public function isOpen(): bool
    {
        return is_resource($this->stream);
    }

    public function close(): void
    {
        if ($this->isOpen()) {
            @fclose($this->stream);
        }
        $this->stream = null;
    }

    public function describe(): string
    {
        return sprintf('network %s:%d', $this->host, $this->port);
    }

    private function stream(): mixed
    {
        if (!$this->isOpen()) {
            $errno = 0;
            $errstr = '';
            $stream = @fsockopen($this->host, $this->port, $errno, $errstr, $this->connectTimeout);
            if ($stream === false) {
                throw new PrinterException(sprintf(
                    'Unable to connect to %s:%d (%s)',
                    $this->host,
                    $this->port,
                    $errstr !== '' ? $errstr : 'unknown error'
                ));
            }
            stream_set_blocking($stream, false);
            $this->stream = $stream;
        }

        return $this->stream;
    }
}
