<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Model\entities\Concert;
use App\Model\entities\ConcertSong;
use App\Model\entities\Song;
use App\Model\repositories\ConcertSongRepository;
use App\Model\repositories\SongRepository;
use App\Services\ConcertSongService;
use Closure;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class ConcertSongServiceTransactionTest extends TestCase
{
    public function testSavingConcertAndAssignmentsUsesOneTransactionAndFlush(): void
    {
        $concert = new Concert();
        $repository = $this->createMock(ConcertSongRepository::class);
        $repository->expects(self::never())->method('findBy');

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('transactional')
            ->willReturnCallback(static fn (Closure $callback): mixed => $callback($connection));

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($connection);
        $entityManager->method('getRepository')->with(ConcertSong::class)->willReturn($repository);
        $entityManager->expects(self::once())->method('persist')->with($concert);
        $entityManager->expects(self::once())->method('flush');

        (new ConcertSongService($entityManager))->saveConcert($concert, []);
    }

    public function testDeletingConcertAndAssignmentsUsesOneTransactionAndFlush(): void
    {
        $concert = new Concert();
        $concertSong = new ConcertSong();
        $repository = $this->createMock(ConcertSongRepository::class);
        $repository->expects(self::once())
            ->method('findBy')
            ->with(['concert' => $concert])
            ->willReturn([$concertSong]);

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('transactional')
            ->willReturnCallback(static fn (Closure $callback): mixed => $callback($connection));

        $removed = [];
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($connection);
        $entityManager->method('getRepository')->with(ConcertSong::class)->willReturn($repository);
        $entityManager->expects(self::exactly(2))
            ->method('remove')
            ->willReturnCallback(static function (object $entity) use (&$removed): void {
                $removed[] = $entity;
            });
        $entityManager->expects(self::once())->method('flush');

        (new ConcertSongService($entityManager))->deleteConcert($concert);

        self::assertSame([$concertSong, $concert], $removed);
    }

    public function testSavingExistingConcertAddsSelectedSongInsideTransaction(): void
    {
        $concert = new Concert();
        $concert->setId(7);
        $song = new Song();
        $song->setId(9);

        $concertSongRepository = $this->createMock(ConcertSongRepository::class);
        $concertSongRepository->expects(self::once())
            ->method('findBy')
            ->with(['concert' => $concert])
            ->willReturn([]);
        $songRepository = $this->createMock(SongRepository::class);
        $songRepository->expects(self::once())->method('find')->with(9)->willReturn($song);

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('transactional')
            ->willReturnCallback(static fn (Closure $callback): mixed => $callback($connection));

        $persisted = [];
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($connection);
        $entityManager->method('getRepository')->willReturnCallback(
            static fn (string $entityClass): ConcertSongRepository|SongRepository => match ($entityClass) {
                ConcertSong::class => $concertSongRepository,
                Song::class => $songRepository,
            },
        );
        $entityManager->expects(self::exactly(2))
            ->method('persist')
            ->willReturnCallback(static function (object $entity) use (&$persisted): void {
                $persisted[] = $entity;
            });
        $entityManager->expects(self::once())->method('flush');

        (new ConcertSongService($entityManager))->saveConcert($concert, [9]);

        self::assertSame($concert, $persisted[0]);
        self::assertInstanceOf(ConcertSong::class, $persisted[1]);
        self::assertSame($concert, $persisted[1]->getConcert());
        self::assertSame($song, $persisted[1]->getSong());
    }
}
