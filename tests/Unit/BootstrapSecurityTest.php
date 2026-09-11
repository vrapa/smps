<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Bootstrap;
use PHPUnit\Framework\TestCase;

final class BootstrapSecurityTest extends TestCase
{
    /** @return iterable<string, array{string|false, bool}> */
    public static function debugModeProvider(): iterable
    {
        yield 'missing' => [false, false];
        yield 'zero' => ['0', false];
        yield 'false' => ['false', false];
        yield 'one' => ['1', true];
        yield 'true' => ['true', true];
    }

    /** @dataProvider debugModeProvider */
    public function testDebugModeRequiresExplicitTruthyEnvironmentValue(
        string|false $environmentValue,
        bool $expected,
    ): void {
        self::assertSame($expected, Bootstrap::isDebugModeRequested($environmentValue));
    }

    /** @return iterable<string, array{string|false, string}> */
    public static function environmentProvider(): iterable
    {
        yield 'missing' => [false, 'local.neon'];
        yield 'production' => ['production', 'local.neon'];
        yield 'similar test name' => ['testing', 'local.neon'];
        yield 'explicit test' => ['test', 'test.neon'];
    }

    /** @dataProvider environmentProvider */
    public function testTestConfigurationRequiresExactEnvironmentValue(
        string|false $environmentValue,
        string $expected,
    ): void {
        self::assertSame($expected, Bootstrap::localConfigName($environmentValue));
    }
}
