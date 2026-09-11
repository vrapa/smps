<?php

declare(strict_types=1);

namespace App\Configuration;

use InvalidArgumentException;

final class PublicSettings
{
    private string $applicationName;

    public function __construct(string $applicationName)
    {
        $applicationName = trim($applicationName);
        if ($applicationName === '') {
            throw new InvalidArgumentException('The public application name must not be empty.');
        }

        $this->applicationName = $applicationName;
    }

    public function getApplicationName(): string
    {
        return $this->applicationName;
    }
}
