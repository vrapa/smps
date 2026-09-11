<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Model\entities\Concert;
use App\Model\entities\ConcertSong;
use App\Model\entities\Song;
use App\Model\repositories\ConcertSongRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

final class ConcertSongMappingCompatibilityTest extends TestCase
{
    public function testRelationKeepsExistingTableColumnsAndTargets(): void
    {
        $reflection = new ReflectionClass(ConcertSong::class);
        $table = $reflection->getAttributes(Table::class)[0]->newInstance();
        $entity = $reflection->getAttributes(Entity::class)[0]->newInstance();

        self::assertSame('skladby2koncerty', $table->name);
        self::assertSame(ConcertSongRepository::class, $entity->repositoryClass);

        $sortOrder = (new ReflectionProperty(ConcertSong::class, 'sortOrder'))
            ->getAttributes(Column::class)[0]
            ->newInstance();
        self::assertSame('priority', $sortOrder->name);
        self::assertTrue($sortOrder->nullable);

        $expectedRelations = [
            'concert' => ['koncerty_id', Concert::class],
            'song' => ['skladby_id', Song::class],
        ];

        foreach ($expectedRelations as $propertyName => [$columnName, $targetEntity]) {
            $property = new ReflectionProperty(ConcertSong::class, $propertyName);
            $join = $property->getAttributes(JoinColumn::class)[0]->newInstance();
            $relation = $property->getAttributes(ManyToOne::class)[0]->newInstance();

            self::assertSame($columnName, $join->name, $propertyName);
            self::assertFalse($join->nullable, $propertyName);
            self::assertSame($targetEntity, $relation->targetEntity, $propertyName);
        }
    }

    public function testSortOrderDefaultsToNull(): void
    {
        self::assertNull((new ConcertSong())->getSortOrder());
    }
}
