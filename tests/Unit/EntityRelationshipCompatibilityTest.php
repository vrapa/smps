<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Model\entities\Concert;
use App\Model\entities\ConcertSong;
use App\Model\entities\Role;
use App\Model\entities\Song;
use App\Model\entities\SongFile;
use App\Model\entities\User;
use App\Model\entities\UserRole;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class EntityRelationshipCompatibilityTest extends TestCase
{
    public function testInverseCollectionsAreInitializedAndMapped(): void
    {
        $collections = [
            [new User(), User::class, 'userRoles', UserRole::class, 'user'],
            [new Role(), Role::class, 'userRoles', UserRole::class, 'role'],
            [new Concert(), Concert::class, 'concertSongs', ConcertSong::class, 'concert'],
            [new Song(), Song::class, 'concertSongs', ConcertSong::class, 'song'],
            [new Song(), Song::class, 'songFiles', SongFile::class, 'song'],
        ];

        foreach ($collections as [$entity, $entityClass, $propertyName, $targetEntity, $mappedBy]) {
            $getter = 'get' . ucfirst($propertyName);
            $collection = $entity->{$getter}();
            $mapping = (new ReflectionProperty($entityClass, $propertyName))
                ->getAttributes(OneToMany::class)[0]
                ->newInstance();

            self::assertInstanceOf(Collection::class, $collection, $propertyName);
            self::assertTrue($collection->isEmpty(), $propertyName);
            self::assertSame($targetEntity, $mapping->targetEntity, $propertyName);
            self::assertSame($mappedBy, $mapping->mappedBy, $propertyName);
            self::assertSame([], $mapping->cascade, $propertyName);
            self::assertFalse($mapping->orphanRemoval, $propertyName);
        }
    }

    public function testOwningRelationsReferenceTheirInverseCollections(): void
    {
        $relations = [
            [UserRole::class, 'user', 'userRoles'],
            [UserRole::class, 'role', 'userRoles'],
            [ConcertSong::class, 'concert', 'concertSongs'],
            [ConcertSong::class, 'song', 'concertSongs'],
            [SongFile::class, 'song', 'songFiles'],
        ];

        foreach ($relations as [$entityClass, $propertyName, $inversedBy]) {
            $mapping = (new ReflectionProperty($entityClass, $propertyName))
                ->getAttributes(ManyToOne::class)[0]
                ->newInstance();

            self::assertSame($inversedBy, $mapping->inversedBy, $propertyName);
        }
    }
}
