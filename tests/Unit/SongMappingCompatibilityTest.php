<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Model\entities\ConcertSong;
use App\Model\entities\Song;
use App\Model\entities\SongFile;
use App\Model\entities\User;
use App\Model\repositories\SongRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

final class SongMappingCompatibilityTest extends TestCase
{
    public function testSongKeepsExistingTableAndColumnNames(): void
    {
        $reflection = new ReflectionClass(Song::class);
        $table = $reflection->getAttributes(Table::class)[0]->newInstance();
        $entity = $reflection->getAttributes(Entity::class)[0]->newInstance();

        self::assertSame('skladby', $table->name);
        self::assertSame(SongRepository::class, $entity->repositoryClass);

        $expectedColumns = [
            'title' => 'nazev',
            'author' => 'autor',
            'active' => 'active',
            'createdAt' => 'createdAt',
        ];

        foreach ($expectedColumns as $propertyName => $columnName) {
            $column = (new ReflectionProperty(Song::class, $propertyName))
                ->getAttributes(Column::class)[0]
                ->newInstance();

            self::assertSame($columnName, $column->name, $propertyName);
        }

        $createdBy = new ReflectionProperty(Song::class, 'createdBy');
        $createdByJoin = $createdBy->getAttributes(JoinColumn::class)[0]->newInstance();
        $createdByRelation = $createdBy->getAttributes(ManyToOne::class)[0]->newInstance();

        self::assertSame('createdBy', $createdByJoin->name);
        self::assertFalse($createdByJoin->nullable);
        self::assertSame(User::class, $createdByRelation->targetEntity);
    }

    public function testExistingRelationsNowTargetSong(): void
    {
        foreach ([ConcertSong::class, SongFile::class] as $relationClass) {
            $song = new ReflectionProperty($relationClass, 'song');
            $join = $song->getAttributes(JoinColumn::class)[0]->newInstance();
            $relation = $song->getAttributes(ManyToOne::class)[0]->newInstance();

            self::assertSame('skladby_id', $join->name, $relationClass);
            self::assertFalse($join->nullable, $relationClass);
            self::assertSame(Song::class, $relation->targetEntity, $relationClass);
        }
    }
}
