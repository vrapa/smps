<?php

declare(strict_types=1);

namespace App\Model\repositories;

use App\Model\entities\ConcertSong;

/**
 * @method ConcertSong|null find($id, ?int $lockMode = null, ?int $lockVersion = null)
 * @method ConcertSong|null findOneBy(array $criteria, array $orderBy = null)
 * @method ConcertSong[] findAll()
 * @method ConcertSong[] findBy(array $criteria, array $orderBy = null, ?int $limit = null, ?int $offset = null)
 * @extends AbstractRepository<ConcertSong>
 */
class ConcertSongRepository extends AbstractRepository
{
}
