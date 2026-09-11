<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Model\entities\Role;
use App\Model\entities\User;
use App\Model\repositories\RoleRepository;
use App\Model\repositories\UserRepository;
use App\Model\UserAuthenticator;
use App\Services\UserService;
use Nette\Security\AuthenticationException;
use Nette\Security\Passwords;

final class UserAuthenticationTest extends DatabaseTestCase
{
    public function testUserPersistenceRolesPaginationAndLogin(): void
    {
        $passwords = new Passwords();
        $user = new User();
        $user->setUsername('integration-user');
        $user->setEmail('integration@example.test');
        $user->setPassword($passwords->hash('correct-password'));
        $user->setName('Integration');
        $user->setSurname('User');
        $user->setDisplayName('Integration User');

        $userService = new UserService($this->entityManager);
        $userService->saveUser($user, [1]);
        $userId = $user->getId();
        $this->entityManager->clear();

        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        self::assertSame(1, $userRepository->countAll());
        self::assertSame('integration-user', $userRepository->findPage(1)[0]->getUsername());
        self::assertSame($userId, $userRepository->findOneByEmail('integration@example.test')?->getId());

        /** @var RoleRepository $roleRepository */
        $roleRepository = $this->entityManager->getRepository(Role::class);
        self::assertSame([
            1 => 'admin',
            3 => 'guest',
            2 => 'user',
        ], $roleRepository->findChoices());

        $authenticator = new UserAuthenticator($this->entityManager, $passwords, $userService);
        try {
            $authenticator->authenticate('integration-user', 'wrong-password');
            self::fail('Invalid password was accepted.');
        } catch (AuthenticationException $exception) {
            self::assertSame('Invalid password.', $exception->getMessage());
        }

        $identity = $authenticator->authenticate('integration-user', 'correct-password');
        self::assertSame($userId, $identity->getId());
        self::assertSame(['admin'], $identity->getRoles());
        self::assertSame('Integration User', $identity->getData()['name']);
    }
}
