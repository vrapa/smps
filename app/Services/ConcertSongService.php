<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\entities\Concert;
use App\Model\entities\ConcertSong;
use App\Model\entities\Song;
use Doctrine\ORM\EntityManagerInterface;

class ConcertSongService
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /** @param array<int, int|string> $songIds */
    public function saveConcert(Concert $concert, array $songIds): void
    {
        $this->entityManager->getConnection()->transactional(function () use ($concert, $songIds): void {
            $this->entityManager->persist($concert);
            $this->synchronizeSongs($concert, $songIds);
            $this->entityManager->flush();
        });
    }

    public function deleteConcert(Concert $concert): void
    {
        $this->entityManager->getConnection()->transactional(function () use ($concert): void {
            foreach ($this->getConcertSongs($concert) as $concertSong) {
                $this->entityManager->remove($concertSong);
            }

            $this->entityManager->remove($concert);
            $this->entityManager->flush();
        });
    }

    /** @param array<int, int|string> $songIds */
    public function synchronizeSongs(Concert $concert, array $songIds): void
    {
        $songIds = array_map('intval', $songIds);
        $existingIds = [];
        $concertSongs = $concert->hasId() ? $this->getConcertSongs($concert) : [];
        foreach ($concertSongs as $concertSong) {
            if (in_array($concertSong->getSong()->getId(), $songIds, true)) {
                $existingIds[] = $concertSong->getSong()->getId();
            } else {
                $this->entityManager->remove($concertSong);
            }
        }

        foreach ($songIds as $songId) {
            if (!in_array($songId, $existingIds, true)) {
                $song = $this->entityManager->getRepository(Song::class)->find($songId);
                if ($song === null) {
                    throw new \UnexpectedValueException("Song {$songId} was not found.");
                }

                $concertSong = new ConcertSong();
                $concertSong->setConcert($concert);
                $concertSong->setSong($song);
                $this->entityManager->persist($concertSong);
            }
        }
    }

    /** @return int[] */
    public function getSongIds(Concert $concert): array
    {
        $ids = [];
        foreach ($this->getConcertSongs($concert) as $concertSong) {
            $ids[] = $concertSong->getSong()->getId();
        }

        return $ids;
    }

    /** @return ConcertSong[] */
    public function getConcertSongs(Concert $concert): array
    {
        return $this->entityManager->getRepository(ConcertSong::class)->findBy(['concert' => $concert]);
    }
}
