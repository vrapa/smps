<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Configuration\PhinxConfigFactory;
use PHPUnit\Framework\TestCase;

final class ProductionPhinxConfigTest extends TestCase
{
    public function testConfigurationReusesProtectedDoctrineConnection(): void
    {
        $temporaryConfigPath = tempnam(sys_get_temp_dir(), 'smps-phinx-');
        self::assertIsString($temporaryConfigPath);
        file_put_contents($temporaryConfigPath, <<<'NEON'
parameters:
    doctrine:
        host: db.example.test
        port: 3307
        user: synthetic-user
        password: synthetic-password
        dbname: synthetic-database
NEON);

        try {
            $configuration = PhinxConfigFactory::fromLocalNeon(
                $temporaryConfigPath,
                '/synthetic/migrations',
                '/synthetic/seeds',
            );
        } finally {
            unlink($temporaryConfigPath);
        }

        self::assertSame('production', $configuration['environments']['default_environment']);
        self::assertSame('/synthetic/migrations', $configuration['paths']['migrations']);
        self::assertSame('/synthetic/seeds', $configuration['paths']['seeds']);
        self::assertSame([
            'adapter' => 'mysql',
            'host' => 'db.example.test',
            'name' => 'synthetic-database',
            'user' => 'synthetic-user',
            'pass' => 'synthetic-password',
            'charset' => 'utf8mb4',
            'port' => 3307,
        ], $configuration['environments']['production']);
    }
}
