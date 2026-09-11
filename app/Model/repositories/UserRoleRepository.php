<?php

declare(strict_types=1);

namespace App\Model\repositories;

use App\Model\entities\UserRole;

/**
 * @method UserRole|null find($id, ?int $lockMode = null, ?int $lockVersion = null)
 * @method UserRole|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserRole[] findAll()
 * @method UserRole[] findBy(array $criteria, array $orderBy = null, ?int $limit = null, ?int $offset = null)
 * @extends AbstractRepository<UserRole>
 */
class UserRoleRepository extends AbstractRepository
{
    /** @return list<int> */
    public function findRoleIdsByUserId(int $userId): array
    {
        $rows = $this->createQueryBuilder('userRole')
            ->select('IDENTITY(userRole.role) AS roleId')
            ->where('IDENTITY(userRole.user) = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getArrayResult();

        return array_map(
            static fn (array $row): int => (int) $row['roleId'],
            $rows,
        );
    }

    /** @return list<string> */
    public function findRoleCodesByUserId(int $userId): array
    {
        $rows = $this->createQueryBuilder('userRole')
            ->select('role.code AS roleCode')
            ->innerJoin('userRole.role', 'role')
            ->where('IDENTITY(userRole.user) = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getArrayResult();

        return array_map(
            static fn (array $row): string => (string) $row['roleCode'],
            $rows,
        );
    }
}
