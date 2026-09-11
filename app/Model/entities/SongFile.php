<?php

declare(strict_types=1);

namespace App\Model\entities;

use App\Model\repositories\SongFileRepository;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'soubory2skladby', options: ['charset' => 'utf8mb4', 'collation' => 'utf8mb4_czech_ci'])]
#[ORM\Index(name: 'skladby_id', columns: ['skladby_id'])]
#[ORM\Index(name: 'createdBy', columns: ['createdBy'])]
#[ORM\Entity(repositoryClass: SongFileRepository::class)]
class SongFile
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $id;

    #[ORM\Column(name: 'kategorie', type: 'string', length: 40, nullable: false)]
    private string $category;

    #[ORM\Column(name: 'filename', type: 'string', length: 150, nullable: false)]
    private string $filename;

    #[ORM\Column(name: 'popis', type: 'string', length: 150, nullable: false)]
    private string $description;

    #[ORM\Column(name: 'createdAt', type: 'datetime', nullable: false)]
    private DateTimeInterface $createdAt;

    #[ORM\Column(name: 'priorita', type: 'integer', nullable: true)]
    private ?int $sortOrder = null;

    #[ORM\JoinColumn(name: 'createdBy', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: User::class)]
    private User $createdBy;

    #[ORM\JoinColumn(name: 'skladby_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: Song::class, inversedBy: 'songFiles')]
    private Song $song;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): void
    {
        $this->category = $category;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): void
    {
        $this->filename = $filename;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(?int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }

    public function getCreatedBy(): User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(User $createdBy): void
    {
        $this->createdBy = $createdBy;
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
