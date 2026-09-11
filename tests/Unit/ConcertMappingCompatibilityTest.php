<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Model\entities\Concert;
use App\Model\entities\ConcertSong;
use App\Model\entities\User;
use App\Model\repositories\ConcertRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

final class ConcertMappingCompatibilityTest extends TestCase
{
    public function testNewConcertHasNoIdentityUntilItIsAssigned(): void
    {
        $concert = new Concert();

        self::assertFalse($concert->hasId());
        $concert->setId(42);
        self::assertTrue($concert->hasId());
    }

    public function testConcertKeepsExistingTableAndColumnNames(): void
    {
        $reflection = new ReflectionClass(Concert::class);
        $table = $reflection->getAttributes(Table::class)[0]->newInstance();
        $entity = $reflection->getAttributes(Entity::class)[0]->newInstance();

        self::assertSame('koncerty', $table->name);
        self::assertSame(ConcertRepository::class, $entity->repositoryClass);

        $expectedColumns = [
            'title' => 'nazev',
            'scheduledAt' => 'kdy',
            'note' => 'poznamka',
            'createdAt' => 'createdAt',
        ];

        foreach ($expectedColumns as $propertyName => $columnName) {
            $column = (new ReflectionProperty(Concert::class, $propertyName))
                ->getAttributes(Column::class)[0]
                ->newInstance();

            self::assertSame($columnName, $column->name, $propertyName);
        }

        $createdBy = new ReflectionProperty(Concert::class, 'createdBy');
        $createdByJoin = $createdBy->getAttributes(JoinColumn::class)[0]->newInstance();
        $createdByRelation = $createdBy->getAttributes(ManyToOne::class)[0]->newInstance();

        self::assertSame('createdBy', $createdByJoin->name);
        self::assertFalse($createdByJoin->nullable);
        self::assertSame(User::class, $createdByRelation->targetEntity);
    }

    public function testExistingRelationNowTargetsConcert(): void
    {
        $concert = new ReflectionProperty(ConcertSong::class, 'concert');
        $join = $concert->getAttributes(JoinColumn::class)[0]->newInstance();
        $relation = $concert->getAttributes(ManyToOne::class)[0]->newInstance();

        self::assertSame('koncerty_id', $join->name);
        self::assertFalse($join->nullable);
        self::assertSame(Concert::class, $relation->targetEntity);
    }
}
