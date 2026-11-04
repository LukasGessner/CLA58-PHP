<?php

declare(strict_types=1);

function cla58_device(): string
{
    $device = getenv('CLA58_DEVICE');

    return $device !== false && $device !== '' ? $device : '/dev/usb/lp0';
}
