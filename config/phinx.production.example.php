<?php

declare(strict_types=1);

use App\Configuration\PhinxConfigFactory;

require_once __DIR__ . '/../vendor/autoload.php';

return PhinxConfigFactory::fromLocalNeon(
    __DIR__ . '/local.neon',
    __DIR__ . '/../db/migrations',
    __DIR__ . '/../db/seeds',
);
