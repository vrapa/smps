<?php

declare(strict_types=1);

namespace App\Model\repositories;

use App\Model\entities\Song;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method Song|null find($id, ?int $lockMode = null, ?int $lockVersion = null)
 * @method Song|null findOneBy(array $criteria, array $orderBy = null)
 * @method Song[] findAll()
 * @method Song[] findBy(array $criteria, array $orderBy = null, ?int $limit = null, ?int $offset = null)
 * @extends AbstractRepository<Song>
 */
class SongRepository extends AbstractRepository
{
    /** @return array<int, string> */
    public function findChoices(): array
    {
        $rows = $this->createQueryBuilder('song')
            ->select('song.id, song.title')
            ->orderBy('song.title', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $choices = [];
        foreach ($rows as $row) {
            $choices[(int) $row['id']] = (string) $row['title'];
        }

        return $choices;
    }

    /**
     * @return Song[]
     */
    public function findPage(int $itemsPerPage = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('a')
            ->setFirstResult($offset)
            ->setMaxResults($itemsPerPage)
            ->orderBy('a.title')
            ->getQuery()->getResult();
    }

    /** @throws NonUniqueResultException */
    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a)')
            ->getQuery()->getSingleScalarResult();
    }
}
