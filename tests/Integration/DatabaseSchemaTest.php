<?php

declare(strict_types=1);

namespace Tests\Integration;

use Doctrine\ORM\Tools\SchemaValidator;

final class DatabaseSchemaTest extends DatabaseTestCase
{
    public function testFreshMigrationMatchesDoctrineMapping(): void
    {
        $connection = $this->entityManager->getConnection();

        $connection->getDatabasePlatform()->registerDoctrineTypeMapping('bit', 'boolean');
        $schemaManager = $connection->createSchemaManager();
        $tableNames = $schemaManager->listTableNames();
        sort($tableNames);

        self::assertSame([
            '_phinxlog',
            'koncerty',
            'roles',
            'roles2users',
            'skladby',
            'skladby2koncerty',
            'soubory2skladby',
            'users',
        ], $tableNames);

        $userColumns = $schemaManager->listTableColumns('users');
        self::assertArrayNotHasKey('role', $userColumns);
        self::assertArrayNotHasKey('deprecated_role', $userColumns);
        self::assertSame(
            'tinyint(1)',
            $connection->fetchOne(
                <<<'SQL'
SELECT COLUMN_TYPE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'skladby'
  AND COLUMN_NAME = 'active'
SQL,
            ),
        );
        self::assertSame(
            ['admin', 'guest', 'user'],
            $connection->fetchFirstColumn('SELECT kod FROM roles ORDER BY kod'),
        );

        $validator = new SchemaValidator($this->entityManager);
        self::assertSame([], $validator->validateMapping());
        self::assertTrue($validator->schemaInSyncWithMetadata());
    }
}
