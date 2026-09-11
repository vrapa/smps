<?php

declare(strict_types=1);

namespace App\Model;

use App\Model\entities\User;
use App\Model\repositories\UserRepository;
use App\Services\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Nette\Security\AuthenticationException;
use Nette\Security\Authenticator;
use Nette\Security\Passwords;
use Nette\Security\SimpleIdentity;

class UserAuthenticator implements Authenticator
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Passwords $passwords,
        private UserService $userService,
    ) {
    }

    public function authenticate(string $username, string $password): SimpleIdentity
    {
        /** @var UserRepository $repository */
        $repository = $this->entityManager->getRepository(User::class);
        $user = $repository->findOneByUsername($username);

        if ($user === null) {
            throw new AuthenticationException('User not found.');
        }

        if (!$this->passwords->verify($password, $user->getPassword())) {
            throw new AuthenticationException('Invalid password.');
        }

        $userId = $user->getId();
        $roles = $this->userService->getRoleCodesByUser($userId);

        return new SimpleIdentity(
            $userId,
            $roles,
            ['name' => $user->getDisplayName()],
        );
    }
}
