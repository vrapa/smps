<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\entities\Role;
use App\Model\entities\User;
use App\Model\entities\UserRole;
use App\Model\repositories\RoleRepository;
use App\Model\repositories\UserRoleRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserService
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /** @return list<int> */
    public function getRoleIdsByUser(int $userId): array
    {
        return $this->getUserRoleRepository()->findRoleIdsByUserId($userId);
    }

    /** @return list<string> */
    public function getRoleCodesByUser(int $userId): array
    {
        return $this->getUserRoleRepository()->findRoleCodesByUserId($userId);
    }

    /** @param array<int, int|string> $roleIds */
    public function saveUser(User $user, array $roleIds): void
    {
        $this->entityManager->getConnection()->transactional(function () use ($user, $roleIds): void {
            $this->entityManager->persist($user);
            $this->synchronizeRoles($user, $roleIds);
            $this->entityManager->flush();
        });
    }

    public function deleteUser(User $user): void
    {
        $this->entityManager->getConnection()->transactional(function () use ($user): void {
            foreach ($this->getUserRoles($user) as $userRole) {
                $this->entityManager->remove($userRole);
            }

            $this->entityManager->remove($user);
            $this->entityManager->flush();
        });
    }

    /** @param array<int, int|string> $roleIds */
    private function synchronizeRoles(User $user, array $roleIds): void
    {
        $roleIds = array_values(array_unique(array_map('intval', $roleIds)));
        $existingIds = [];
        $userRoles = $user->hasId() ? $this->getUserRoles($user) : [];

        foreach ($userRoles as $userRole) {
            $roleId = $userRole->getRole()->getId();
            if (in_array($roleId, $roleIds, true)) {
                $existingIds[] = $roleId;
            } else {
                $this->entityManager->remove($userRole);
            }
        }

        /** @var RoleRepository $roleRepository */
        $roleRepository = $this->entityManager->getRepository(Role::class);
        foreach ($roleIds as $roleId) {
            if (in_array($roleId, $existingIds, true)) {
                continue;
            }

            $role = $roleRepository->find($roleId);
            if ($role === null) {
                throw new \UnexpectedValueException("Role {$roleId} was not found.");
            }

            $userRole = new UserRole();
            $userRole->setUser($user);
            $userRole->setRole($role);
            $this->entityManager->persist($userRole);
        }
    }

    /** @return UserRole[] */
    private function getUserRoles(User $user): array
    {
        return $this->getUserRoleRepository()->findBy(['user' => $user]);
    }

    private function getUserRoleRepository(): UserRoleRepository
    {
        /** @var UserRoleRepository $repository */
        $repository = $this->entityManager->getRepository(UserRole::class);

        return $repository;
    }
}
