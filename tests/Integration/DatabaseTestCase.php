<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Bootstrap;
use Doctrine\ORM\EntityManagerInterface;
use Nette\DI\Container;
use Nette\Security\User;
use PHPUnit\Framework\TestCase;

abstract class DatabaseTestCase extends TestCase
{
    protected EntityManagerInterface $entityManager;
    protected Container $container;

    protected function setUp(): void
    {
        if (getenv('SMPS_DATABASE_TESTS') !== '1') {
            self::markTestSkipped('Database integration tests are not enabled.');
        }

        $configurator = Bootstrap::boot();
        $configurator->addStaticParameters(['scope' => 'cli']);
        $this->container = $configurator->createContainer();
        $this->entityManager = $this->container->getByType(EntityManagerInterface::class);

        $databaseName = $this->entityManager->getConnection()->getDatabase();
        self::assertIsString($databaseName);
        self::assertStringEndsWith('_test', $databaseName);

        $this->entityManager->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        if (!isset($this->entityManager)) {
            return;
        }

        $user = $this->container->getByType(User::class);
        if ($user->isLoggedIn()) {
            $user->logout(true);
        }

        $connection = $this->entityManager->getConnection();
        if ($connection->isTransactionActive()) {
            $connection->rollBack();
        }

        $this->entityManager->clear();
    }
}
