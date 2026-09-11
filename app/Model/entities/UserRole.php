<?php

declare(strict_types=1);

namespace App\Model\entities;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'roles2users', options: ['charset' => 'utf8mb4', 'collation' => 'utf8mb4_czech_ci'])]
#[ORM\Index(name: 'roles_id', columns: ['roles_id'])]
#[ORM\Index(name: 'users_id', columns: ['users_id'])]
#[ORM\Entity(repositoryClass: 'App\Model\repositories\UserRoleRepository')]
class UserRole
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Role::class, inversedBy: 'userRoles')]
    #[ORM\JoinColumn(name: 'roles_id', referencedColumnName: 'id', nullable: false)]
    private Role $role;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'userRoles')]
    #[ORM\JoinColumn(name: 'users_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getRole(): Role
    {
        return $this->role;
    }

    public function setRole(Role $role): void
    {
        $this->role = $role;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }
}
