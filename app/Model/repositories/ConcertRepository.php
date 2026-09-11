<?php

declare(strict_types=1);

namespace App\Model\repositories;

use App\Model\entities\Concert;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

/**
 * @method Concert|null find($id, ?int $lockMode = null, ?int $lockVersion = null)
 * @method Concert|null findOneBy(array $criteria, array $orderBy = null)
 * @method Concert[] findAll()
 * @method Concert[] findBy(array $criteria, array $orderBy = null, ?int $limit = null, ?int $offset = null)
 * @extends AbstractRepository<Concert>
 */
class ConcertRepository extends AbstractRepository
{
    /**
     * @return Concert[]
     */
    public function findPage(int $itemsPerPage = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('a')
            ->setFirstResult($offset)
            ->setMaxResults($itemsPerPage)
            ->orderBy('a.scheduledAt', 'DESC')
            ->getQuery()->getResult();
    }

    /**
     * @return int
     * @throws NonUniqueResultException|NoResultException
     */
    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a)')
            ->getQuery()->getSingleScalarResult();
    }
}
