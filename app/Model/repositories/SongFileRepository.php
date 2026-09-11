<?php

declare(strict_types=1);

namespace App\Model\repositories;

use App\Model\entities\SongFile;

/**
 * @method SongFile|null find($id, ?int $lockMode = null, ?int $lockVersion = null)
 * @method SongFile|null findOneBy(array $criteria, array $orderBy = null)
 * @method SongFile[] findAll()
 * @method SongFile[] findBy(array $criteria, array $orderBy = null, ?int $limit = null, ?int $offset = null)
 * @extends AbstractRepository<SongFile>
 */
class SongFileRepository extends AbstractRepository
{
}
