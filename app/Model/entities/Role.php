<?php

declare(strict_types=1);

namespace App\Model\entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'roles', options: ['charset' => 'utf8mb4', 'collation' => 'utf8mb4_czech_ci'])]
#[ORM\Entity(repositoryClass: 'App\Model\repositories\RoleRepository')]
class Role
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $id;

    #[ORM\Column(name: 'kod', type: 'string', length: 30, nullable: false)]
    private string $code;

    #[ORM\Column(name: 'popis', type: 'text', length: 65535, nullable: false)]
    private string $description;

    /** @var Collection<int, UserRole> */
    #[ORM\OneToMany(targetEntity: UserRole::class, mappedBy: 'role', cascade: [], orphanRemoval: false)]
    private Collection $userRoles;

    public function __construct()
    {
        $this->userRoles = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /** @return Collection<int, UserRole> */
    public function getUserRoles(): Collection
    {
        return $this->userRoles;
    }
}
