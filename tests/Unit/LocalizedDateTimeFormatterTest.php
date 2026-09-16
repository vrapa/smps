<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Localization\LocalizedDateTimeFormatter;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class LocalizedDateTimeFormatterTest extends TestCase
{
    public function testConcertDateUsesConfiguredLocaleWithoutChangingWallTime(): void
    {
        $date = new DateTimeImmutable('2026-10-15 19:30:00', new DateTimeZone('UTC'));
        $formatted = [];
        foreach (['cs', 'en', 'de', 'nl'] as $locale) {
            $formatted[$locale] = (new LocalizedDateTimeFormatter($locale, 'Europe/Prague'))
                ->formatConcertDate($date);
            self::assertStringContainsString('2026', $formatted[$locale]);
            self::assertStringContainsString('19:30', $formatted[$locale]);
        }

        self::assertMatchesRegularExpression('/15\.\s*10\.\s*2026/', $formatted['cs']);
        self::assertStringContainsStringIgnoringCase('Oct', $formatted['en']);
        self::assertMatchesRegularExpression('/15\.10\.2026/', $formatted['de']);
        self::assertStringContainsStringIgnoringCase('okt', $formatted['nl']);
        self::assertCount(4, array_unique($formatted));
    }

    public function testLocalInputUsesConfiguredTimezoneAndRejectsInvalidDates(): void
    {
        $formatter = new LocalizedDateTimeFormatter('en', 'Europe/Amsterdam');
        $date = $formatter->parseLocalInput('2026-10-15T19:30');

        self::assertSame('Europe/Amsterdam', $date->getTimezone()->getName());
        self::assertSame('2026-10-15T19:30', $date->format('Y-m-d\TH:i'));

        $this->expectException(RuntimeException::class);
        $formatter->parseLocalInput('2026-02-31T19:30');
    }
}
