<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Model\entities\User;
use App\Model\UserAuthenticator;
use App\Model\repositories\UserRepository;
use App\Services\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Nette\Security\AuthenticationException;
use Nette\Security\Passwords;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class UserAuthenticatorTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private UserRepository&MockObject $userRepository;
    private UserService&MockObject $userService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->userService = $this->createMock(UserService::class);

        $this->entityManager
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($this->userRepository);
    }

    public function testValidCredentialsPreserveIdentityDataAndRoles(): void
    {
        $passwords = new Passwords();
        $user = new User();
        $user->setId(42);
        $user->setUsername('test-user');
        $user->setPassword($passwords->hash('correct-password'));
        $user->setDisplayName('Test User');

        $this->userRepository
            ->expects(self::once())
            ->method('findOneByUsername')
            ->with('test-user')
            ->willReturn($user);
        $this->userService
            ->expects(self::once())
            ->method('getRoleCodesByUser')
            ->with(42)
            ->willReturn(['admin', 'member']);

        $identity = (new UserAuthenticator($this->entityManager, $passwords, $this->userService))
            ->authenticate('test-user', 'correct-password');

        self::assertSame(42, $identity->getId());
        self::assertSame(['admin', 'member'], $identity->getRoles());
        self::assertSame('Test User', $identity->getData()['name']);
    }

    public function testUnknownUsernameIsRejectedBeforeRoleLookup(): void
    {
        $this->userRepository
            ->method('findOneByUsername')
            ->with('missing-user')
            ->willReturn(null);
        $this->userService->expects(self::never())->method('getRoleCodesByUser');

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('User not found.');

        (new UserAuthenticator($this->entityManager, new Passwords(), $this->userService))
            ->authenticate('missing-user', 'irrelevant');
    }

    public function testInvalidPasswordIsRejectedBeforeRoleLookup(): void
    {
        $passwords = new Passwords();
        $user = new User();
        $user->setPassword($passwords->hash('correct-password'));

        $this->userRepository->method('findOneByUsername')->willReturn($user);
        $this->userService->expects(self::never())->method('getRoleCodesByUser');

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid password.');

        (new UserAuthenticator($this->entityManager, $passwords, $this->userService))
            ->authenticate('test-user', 'wrong-password');
    }
}
