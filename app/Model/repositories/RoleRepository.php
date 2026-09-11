<?php

declare(strict_types=1);

namespace App\Model\repositories;

use App\Model\entities\Role;

/**
 * @method Role|null find($id, ?int $lockMode = null, ?int $lockVersion = null)
 * @method Role|null findOneBy(array $criteria, array $orderBy = null)
 * @method Role[] findAll()
 * @method Role[] findBy(array $criteria, array $orderBy = null, ?int $limit = null, ?int $offset = null)
 * @extends AbstractRepository<Role>
 */
class RoleRepository extends AbstractRepository
{
    /** @return array<int, string> */
    public function findChoices(): array
    {
        $rows = $this->createQueryBuilder('role')
            ->select('role.id, role.code')
            ->orderBy('role.code', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $choices = [];
        foreach ($rows as $row) {
            $choices[(int) $row['id']] = (string) $row['code'];
        }

        return $choices;
    }
}
