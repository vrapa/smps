<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Model\entities\UserRole;
use App\Model\repositories\UserRoleRepository;
use App\Services\UserService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class UserServiceTest extends TestCase
{
    public function testRoleLookupsDelegateToDoctrineRepository(): void
    {
        $repository = $this->createMock(UserRoleRepository::class);
        $repository->expects(self::once())
            ->method('findRoleIdsByUserId')
            ->with(42)
            ->willReturn([1, 3]);
        $repository->expects(self::once())
            ->method('findRoleCodesByUserId')
            ->with(42)
            ->willReturn(['admin', 'member']);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::exactly(2))
            ->method('getRepository')
            ->with(UserRole::class)
            ->willReturn($repository);

        $service = new UserService($entityManager);

        self::assertSame([1, 3], $service->getRoleIdsByUser(42));
        self::assertSame(['admin', 'member'], $service->getRoleCodesByUser(42));
    }
}
