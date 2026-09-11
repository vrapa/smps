<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Model\entities\Song;
use App\Model\entities\SongFile;
use App\Model\entities\User;
use App\Model\repositories\SongFileRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

final class SongFileMappingCompatibilityTest extends TestCase
{
    public function testSongFileKeepsExistingTableColumnsAndTargets(): void
    {
        $reflection = new ReflectionClass(SongFile::class);
        $table = $reflection->getAttributes(Table::class)[0]->newInstance();
        $entity = $reflection->getAttributes(Entity::class)[0]->newInstance();

        self::assertSame('soubory2skladby', $table->name);
        self::assertSame(SongFileRepository::class, $entity->repositoryClass);

        $expectedColumns = [
            'category' => 'kategorie',
            'filename' => 'filename',
            'description' => 'popis',
            'createdAt' => 'createdAt',
            'sortOrder' => 'priorita',
        ];

        foreach ($expectedColumns as $propertyName => $columnName) {
            $column = (new ReflectionProperty(SongFile::class, $propertyName))
                ->getAttributes(Column::class)[0]
                ->newInstance();

            self::assertSame($columnName, $column->name, $propertyName);
        }

        $expectedRelations = [
            'createdBy' => ['createdBy', User::class],
            'song' => ['skladby_id', Song::class],
        ];

        foreach ($expectedRelations as $propertyName => [$columnName, $targetEntity]) {
            $property = new ReflectionProperty(SongFile::class, $propertyName);
            $join = $property->getAttributes(JoinColumn::class)[0]->newInstance();
            $relation = $property->getAttributes(ManyToOne::class)[0]->newInstance();

            self::assertSame($columnName, $join->name, $propertyName);
            self::assertFalse($join->nullable, $propertyName);
            self::assertSame($targetEntity, $relation->targetEntity, $propertyName);
        }

        $songJoin = (new ReflectionProperty(SongFile::class, 'song'))
            ->getAttributes(JoinColumn::class)[0]
            ->newInstance();
        self::assertSame('CASCADE', $songJoin->onDelete);
    }

    public function testSortOrderDefaultsToNull(): void
    {
        self::assertNull((new SongFile())->getSortOrder());
    }
}
