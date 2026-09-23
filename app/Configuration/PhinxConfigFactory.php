<?php

declare(strict_types=1);

namespace App\Configuration;

use Nette\Neon\Neon;
use RuntimeException;
use UnexpectedValueException;

final class PhinxConfigFactory
{
    /** @return array<string, mixed> */
    public static function fromLocalNeon(
        string $localConfigPath,
        string $migrationsPath,
        string $seedsPath,
    ): array {
        if (!is_file($localConfigPath) || !is_readable($localConfigPath)) {
            throw new RuntimeException('The protected Nette local configuration is not readable.');
        }

        $localConfig = Neon::decodeFile($localConfigPath);
        $connection = $localConfig['parameters']['doctrine'] ?? null;
        if (!is_array($connection)) {
            throw new UnexpectedValueException('The protected Nette configuration has no Doctrine connection.');
        }

        foreach (['host', 'user', 'password', 'dbname'] as $key) {
            if (!array_key_exists($key, $connection) || (!is_scalar($connection[$key]) && $connection[$key] !== null)) {
                throw new UnexpectedValueException("The Doctrine connection is missing '$key'.");
            }
        }
        foreach (['host', 'user', 'dbname'] as $key) {
            if ((string) $connection[$key] === '') {
                throw new UnexpectedValueException("The Doctrine connection has an empty '$key'.");
            }
        }

        $production = [
            'adapter' => 'mysql',
            'host' => (string) $connection['host'],
            'name' => (string) $connection['dbname'],
            'user' => (string) $connection['user'],
            'pass' => (string) ($connection['password'] ?? ''),
            'charset' => 'utf8mb4',
        ];
        if (isset($connection['port']) && is_numeric($connection['port'])) {
            $production['port'] = (int) $connection['port'];
        }

        return [
            'paths' => [
                'migrations' => $migrationsPath,
                'seeds' => $seedsPath,
            ],
            'environments' => [
                'default_migration_table' => '_phinxlog',
                'default_environment' => 'production',
                'production' => $production,
            ],
            'version_order' => 'creation',
        ];
    }
}
