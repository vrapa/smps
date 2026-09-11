<?php

declare(strict_types=1);

namespace App\Model\entities;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(
    name: 'skladby2koncerty',
    options: ['charset' => 'utf8mb3', 'collation' => 'utf8mb3_czech_ci'],
)]
#[ORM\Index(name: 'skladby_id', columns: ['skladby_id'])]
#[ORM\Index(name: 'koncerty_id', columns: ['koncerty_id'])]
#[ORM\Entity(repositoryClass: 'App\Model\repositories\ConcertSongRepository')]
class ConcertSong
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $id;

    #[ORM\Column(name: 'priority', type: 'integer', nullable: true)]
    private ?int $sortOrder = null;

    #[ORM\ManyToOne(targetEntity: Concert::class, inversedBy: 'concertSongs')]
    #[ORM\JoinColumn(name: 'koncerty_id', referencedColumnName: 'id', nullable: false)]
    private Concert $concert;

    #[ORM\ManyToOne(targetEntity: Song::class, inversedBy: 'concertSongs')]
    #[ORM\JoinColumn(name: 'skladby_id', referencedColumnName: 'id', nullable: false)]
    private Song $song;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(?int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }

    public function getConcert(): Concert
    {
        return $this->concert;
    }

    public function setConcert(Concert $concert): void
    {
        $this->concert = $concert;
    }

    public function getSong(): Song
    {
        return $this->song;
    }

    public function setSong(Song $song): void
    {
        $this->song = $song;
    }
}
