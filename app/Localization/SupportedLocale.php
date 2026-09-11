<?php

declare(strict_types=1);

namespace App\Localization;

use InvalidArgumentException;
use ValueError;

enum SupportedLocale: string
{
    case Czech = 'cs';
    case English = 'en';
    case German = 'de';
    case Dutch = 'nl';

    public static function fromConfiguration(string $locale): self
    {
        try {
            return self::from($locale);
        } catch (ValueError) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported application locale "%s". Allowed locales: %s.',
                $locale,
                implode(', ', self::values()),
            ));
        }
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(
            static fn (self $locale): string => $locale->value,
            self::cases(),
        );
    }
}
