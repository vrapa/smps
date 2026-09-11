<?php

declare(strict_types=1);

namespace App\Model\repositories;

use Doctrine\ORM\EntityRepository;

/**
 * @phpstan-template TEntityClass of object
 * @phpstan-extends EntityRepository<TEntityClass>
 */
abstract class AbstractRepository extends EntityRepository
{
}
