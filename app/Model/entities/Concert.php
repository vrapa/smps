<?php

declare(strict_types=1);

namespace App\Model\entities;

use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'koncerty', options: ['charset' => 'utf8mb3', 'collation' => 'utf8mb3_czech_ci'])]
#[ORM\Index(name: 'createdBy', columns: ['createdBy'])]
#[Entity(repositoryClass: 'App\Model\repositories\ConcertRepository')]
class Concert
{
    #[Id, Column(name: 'id', type: "integer", nullable: false), GeneratedValue(strategy: "IDENTITY")]
    private int $id;

    #[Column(name: 'nazev', type: "string", length: 40, nullable: false)]
    private string $title;

    #[Column(name: "kdy", type: "datetime", nullable: false)]
    private DateTimeInterface $scheduledAt;

    #[ORM\Column(name: 'poznamka', type: 'text', length: 65535, nullable: false)]
    private string $note;

    #[ORM\Column(name: 'createdAt', type: 'datetime', nullable: false)]
    private DateTimeInterface $createdAt;

    #[ORM\JoinColumn(name: 'createdBy', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: User::class)]
    private User $createdBy;

    /** @var Collection<int, ConcertSong> */
    #[ORM\OneToMany(targetEntity: ConcertSong::class, mappedBy: 'concert', cascade: [], orphanRemoval: false)]
    private Collection $concertSongs;

    public function __construct()
    {
        $this->concertSongs = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function hasId(): bool
    {
        return isset($this->id);
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

    public function getScheduledAt(): DateTimeInterface
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(DateTimeInterface $scheduledAt): void
    {
        $this->scheduledAt = $scheduledAt;
    }

    public function getNote(): string
    {
        return $this->note;
    }

    public function setNote(string $note): void
    {
        $this->note = $note;
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
}
