<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Model\entities\Role;
use App\Model\entities\User;
use App\Model\entities\UserRole;
use App\Model\repositories\RoleRepository;
use App\Model\repositories\UserRoleRepository;
use App\Services\UserService;
use Closure;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class UserServiceTransactionTest extends TestCase
{
    public function testSavingNewUserAndRoleUsesOneTransactionAndFlush(): void
    {
        $user = new User();
        $role = new Role();
        $role->setId(3);

        $userRoleRepository = $this->createMock(UserRoleRepository::class);
        $userRoleRepository->expects(self::never())->method('findBy');
        $roleRepository = $this->createMock(RoleRepository::class);
        $roleRepository->expects(self::once())->method('find')->with(3)->willReturn($role);

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('transactional')
            ->willReturnCallback(static fn (Closure $callback): mixed => $callback($connection));

        $persisted = [];
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($connection);
        $entityManager->method('getRepository')->willReturnCallback(
            static fn (string $entityClass): RoleRepository|UserRoleRepository => match ($entityClass) {
                Role::class => $roleRepository,
                UserRole::class => $userRoleRepository,
            },
        );
        $entityManager->expects(self::exactly(2))
            ->method('persist')
            ->willReturnCallback(static function (object $entity) use (&$persisted): void {
                $persisted[] = $entity;
            });
        $entityManager->expects(self::once())->method('flush');

        (new UserService($entityManager))->saveUser($user, [3]);

        self::assertSame($user, $persisted[0]);
        self::assertInstanceOf(UserRole::class, $persisted[1]);
        self::assertSame($user, $persisted[1]->getUser());
        self::assertSame($role, $persisted[1]->getRole());
    }

    public function testDeletingUserAndRolesUsesOneTransactionAndFlush(): void
    {
        $user = new User();
        $user->setId(42);
        $userRole = new UserRole();

        $repository = $this->createMock(UserRoleRepository::class);
        $repository->expects(self::once())
            ->method('findBy')
            ->with(['user' => $user])
            ->willReturn([$userRole]);

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('transactional')
            ->willReturnCallback(static fn (Closure $callback): mixed => $callback($connection));

        $removed = [];
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($connection);
        $entityManager->method('getRepository')->with(UserRole::class)->willReturn($repository);
        $entityManager->expects(self::exactly(2))
            ->method('remove')
            ->willReturnCallback(static function (object $entity) use (&$removed): void {
                $removed[] = $entity;
            });
        $entityManager->expects(self::once())->method('flush');

        (new UserService($entityManager))->deleteUser($user);

        self::assertSame([$userRole, $user], $removed);
    }
}
