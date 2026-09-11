<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Model\entities\Concert;
use App\Model\entities\Song;
use App\Model\entities\SongFile;
use App\Model\entities\User;
use App\Model\repositories\ConcertRepository;
use App\Model\repositories\SongFileRepository;
use App\Model\repositories\SongRepository;
use App\Services\ConcertSongService;
use DateTimeImmutable;

final class SongConcertFileTest extends DatabaseTestCase
{
    public function testPersistencePaginationFilesAndConcertSongSynchronization(): void
    {
        $creator = new User();
        $creator->setUsername('music-test-user');
        $creator->setEmail('music-integration@example.test');
        $creator->setPassword(str_repeat('x', 60));
        $creator->setName('Music');
        $creator->setSurname('Tester');
        $this->entityManager->persist($creator);

        $alpha = $this->createSong('Alpha', 'Composer A', $creator);
        $beta = $this->createSong('Beta', 'Composer B', $creator);
        $this->entityManager->persist($alpha);
        $this->entityManager->persist($beta);
        $this->entityManager->flush();

        $songFile = new SongFile();
        $songFile->setSong($alpha);
        $songFile->setCategory('noty_sbor');
        $songFile->setFilename('integration-score.pdf');
        $songFile->setDescription('Integration score');
        $songFile->setCreatedAt(new DateTimeImmutable('2026-08-21 10:00:00'));
        $songFile->setCreatedBy($creator);
        $songFile->setSortOrder(5);
        $this->entityManager->persist($songFile);

        $olderConcert = $this->createConcert(
            'Older concert',
            new DateTimeImmutable('2026-09-01 18:00:00'),
            $creator,
        );
        $newerConcert = $this->createConcert(
            'Newer concert',
            new DateTimeImmutable('2026-10-01 18:00:00'),
            $creator,
        );
        $concertSongService = new ConcertSongService($this->entityManager);
        $concertSongService->saveConcert($olderConcert, [$alpha->getId(), $beta->getId()]);
        $concertSongService->saveConcert($newerConcert, [$beta->getId()]);
        $olderConcertId = $olderConcert->getId();
        $newerConcertId = $newerConcert->getId();
        $alphaId = $alpha->getId();
        $betaId = $beta->getId();
        $this->entityManager->flush();
        $this->entityManager->clear();

        /** @var SongRepository $songRepository */
        $songRepository = $this->entityManager->getRepository(Song::class);
        self::assertSame(2, $songRepository->countAll());
        self::assertSame(['Alpha', 'Beta'], array_values($songRepository->findChoices()));
        self::assertSame('Beta', $songRepository->findPage(1, 1)[0]->getTitle());

        /** @var ConcertRepository $concertRepository */
        $concertRepository = $this->entityManager->getRepository(Concert::class);
        self::assertSame(2, $concertRepository->countAll());
        self::assertSame('Newer concert', $concertRepository->findPage(1)[0]->getTitle());

        /** @var SongFileRepository $songFileRepository */
        $songFileRepository = $this->entityManager->getRepository(SongFile::class);
        $storedFile = $songFileRepository->findOneBy(['song' => $alphaId]);
        self::assertNotNull($storedFile);
        self::assertSame('integration-score.pdf', $storedFile->getFilename());
        self::assertSame('noty_sbor', $storedFile->getCategory());
        self::assertSame(5, $storedFile->getSortOrder());

        $storedOlderConcert = $concertRepository->find($olderConcertId);
        self::assertNotNull($storedOlderConcert);
        $songIds = $concertSongService->getSongIds($storedOlderConcert);
        sort($songIds);
        self::assertSame([$alphaId, $betaId], $songIds);

        $concertSongService->saveConcert($storedOlderConcert, [$betaId]);
        self::assertSame([$betaId], $concertSongService->getSongIds($storedOlderConcert));

        $storedNewerConcert = $concertRepository->find($newerConcertId);
        self::assertNotNull($storedNewerConcert);
        $concertSongService->deleteConcert($storedNewerConcert);
        self::assertSame(1, $concertRepository->countAll());
    }

    private function createSong(string $title, string $author, User $creator): Song
    {
        $song = new Song();
        $song->setTitle($title);
        $song->setAuthor($author);
        $song->setActive(true);
        $song->setCreatedAt(new DateTimeImmutable('2026-08-21 10:00:00'));
        $song->setCreatedBy($creator);

        return $song;
    }

    private function createConcert(string $title, DateTimeImmutable $scheduledAt, User $creator): Concert
    {
        $concert = new Concert();
        $concert->setTitle($title);
        $concert->setScheduledAt($scheduledAt);
        $concert->setNote('Integration note');
        $concert->setCreatedAt(new DateTimeImmutable('2026-08-21 10:00:00'));
        $concert->setCreatedBy($creator);

        return $concert;
    }
}
