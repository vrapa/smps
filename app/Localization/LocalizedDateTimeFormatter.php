<?php

declare(strict_types=1);

namespace App\Localization;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use IntlDateFormatter;
use RuntimeException;

final class LocalizedDateTimeFormatter
{
    private const LOCALE_TAGS = [
        'cs' => 'cs_CZ',
        'en' => 'en_GB',
        'de' => 'de_DE',
        'nl' => 'nl_NL',
    ];

    private const CONCERT_PATTERNS = [
        'cs' => 'd. M. y H:mm',
        'en' => 'd MMM y, HH:mm',
        'de' => 'dd.MM.y, HH:mm',
        'nl' => 'd MMM y, HH:mm',
    ];

    private SupportedLocale $locale;
    private DateTimeZone $timezone;

    public function __construct(string $locale, string $timezone)
    {
        $this->locale = SupportedLocale::fromConfiguration($locale);
        $this->timezone = new DateTimeZone($timezone);

        if (!class_exists(IntlDateFormatter::class)) {
            throw new RuntimeException('The PHP Intl extension is required for localized dates.');
        }
    }

    public function formatConcertDate(DateTimeInterface $dateTime): string
    {
        $wallTime = DateTimeImmutable::createFromFormat(
            '!Y-m-d H:i:s',
            $dateTime->format('Y-m-d H:i:s'),
            $this->timezone,
        );
        if ($wallTime === false) {
            throw new RuntimeException('The concert date could not be formatted.');
        }

        $locale = $this->locale->value;
        $formatter = new IntlDateFormatter(
            self::LOCALE_TAGS[$locale],
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            $this->timezone,
            IntlDateFormatter::GREGORIAN,
            self::CONCERT_PATTERNS[$locale],
        );
        $formatted = $formatter->format($wallTime);
        if ($formatted === false) {
            throw new RuntimeException('The concert date could not be formatted.');
        }

        return $formatted;
    }

    public function parseLocalInput(string $value): DateTimeImmutable
    {
        $dateTime = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value, $this->timezone);
        $errors = DateTimeImmutable::getLastErrors();
        if (
            $dateTime === false
            || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
            || $dateTime->format('Y-m-d\TH:i') !== $value
        ) {
            throw new RuntimeException('The concert date has an invalid format.');
        }

        return $dateTime;
    }
}
