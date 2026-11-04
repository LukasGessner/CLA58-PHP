<?php

declare(strict_types=1);

namespace Cla58\Connection;

interface ConnectionInterface
{
    public function write(string $data): int;

    public function read(int $length = 64, float $timeout = 1.0): string;

    public function isOpen(): bool;

    public function close(): void;

    public function describe(): string;
}
