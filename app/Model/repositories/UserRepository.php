<?php

declare(strict_types=1);

namespace App\Model\repositories;

use App\Model\entities\User;

/**
 * @method User|null find($id, ?int $lockMode = null, ?int $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[] findAll()
 * @method User[] findBy(array $criteria, array $orderBy = null, ?int $limit = null, ?int $offset = null)
 * @extends AbstractRepository<User>
 */
class UserRepository extends AbstractRepository
{
    /** @return User[] */
    public function findPage(int $itemsPerPage = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('user')
            ->orderBy('user.username', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($itemsPerPage)
            ->getQuery()
            ->getResult();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('user')
            ->select('COUNT(user)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findOneByUsername(string $username): ?User
    {
        return $this->findOneBy(['username' => $username]);
    }

    public function findOneByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }
}
