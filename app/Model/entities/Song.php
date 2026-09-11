<?php

declare(strict_types=1);

namespace App\Model\entities;

use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'skladby', options: ['charset' => 'utf8mb3', 'collation' => 'utf8mb3_czech_ci'])]
#[ORM\Index(name: 'createdBy', columns: ['createdBy'])]
#[ORM\Entity(repositoryClass: 'App\Model\repositories\SongRepository')]
class Song
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $id;

    #[ORM\Column(name: 'nazev', type: 'string', length: 40, nullable: false)]
    private string $title;

    #[ORM\Column(name: 'autor', type: 'string', length: 40, nullable: false)]
    private string $author;

    #[ORM\Column(name: 'active', type: 'boolean', nullable: false)]
    private bool $active;

    #[ORM\Column(name: 'createdAt', type: 'datetime', nullable: false)]
    private DateTimeInterface $createdAt;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'createdBy', referencedColumnName: 'id', nullable: false)]
    private User $createdBy;

    /** @var Collection<int, ConcertSong> */
    #[ORM\OneToMany(targetEntity: ConcertSong::class, mappedBy: 'song', cascade: [], orphanRemoval: false)]
    private Collection $concertSongs;

    /** @var Collection<int, SongFile> */
    #[ORM\OneToMany(targetEntity: SongFile::class, mappedBy: 'song', cascade: [], orphanRemoval: false)]
    private Collection $songFiles;

    public function __construct()
    {
        $this->concertSongs = new ArrayCollection();
        $this->songFiles = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function setAuthor(string $author): void
    {
        $this->author = $author;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getCreatedBy(): User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(User $createdBy): void
    {
        $this->createdBy = $createdBy;
    }

    /** @return Collection<int, ConcertSong> */
    public function getConcertSongs(): Collection
    {
        return $this->concertSongs;
    }

    /** @return Collection<int, SongFile> */
    public function getSongFiles(): Collection
    {
        return $this->songFiles;
    }
}
