<?php

declare(strict_types=1);

namespace App\Model\entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'users', options: ['charset' => 'utf8mb3', 'collation' => 'utf8mb3_general_ci'])]
#[ORM\UniqueConstraint(name: 'username_2', columns: ['username'])]
#[ORM\UniqueConstraint(name: 'username', columns: ['email'])]
#[ORM\Entity(repositoryClass: 'App\Model\repositories\UserRepository')]
class User
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $id;

    #[ORM\Column(
        name: 'username',
        type: 'string',
        length: 20,
        nullable: false,
        options: ['comment' => 'login', 'collation' => 'utf8mb3_czech_ci'],
    )]
    private string $username;

    #[ORM\Column(
        name: 'nazev',
        type: 'string',
        length: 90,
        nullable: true,
        options: ['comment' => 'nazev firmy', 'collation' => 'utf8mb3_czech_ci'],
    )]
    private ?string $displayName = null;

    #[ORM\Column(
        name: 'ico',
        type: 'string',
        length: 8,
        nullable: true,
        options: ['comment' => 'IČO', 'collation' => 'utf8mb3_czech_ci'],
    )]
    private ?string $companyRegistrationNumber = null;

    #[ORM\Column(
        name: 'dico',
        type: 'string',
        length: 10,
        nullable: true,
        options: ['comment' => 'DIČO', 'collation' => 'utf8mb3_czech_ci'],
    )]
    private ?string $vatIdentificationNumber = null;

    #[ORM\Column(
        name: 'ulice',
        type: 'string',
        length: 50,
        nullable: true,
        options: ['comment' => 'ulice', 'collation' => 'utf8mb3_czech_ci'],
    )]
    private ?string $street = null;

    #[ORM\Column(
        name: 'cislo_popisne',
        type: 'string',
        length: 15,
        nullable: true,
        options: ['comment' => 'cp', 'collation' => 'utf8mb3_czech_ci'],
    )]
    private ?string $streetNumber = null;

    #[ORM\Column(
        name: 'mesto',
        type: 'string',
        length: 40,
        nullable: true,
        options: ['collation' => 'utf8mb3_czech_ci'],
    )]
    private ?string $city = null;

    #[ORM\Column(
        name: 'psc',
        type: 'string',
        length: 5,
        nullable: true,
        options: ['collation' => 'utf8mb3_czech_ci'],
    )]
    private ?string $postalCode = null;

    #[ORM\Column(
        name: 'telefon',
        type: 'string',
        length: 20,
        nullable: true,
        options: ['collation' => 'utf8mb3_czech_ci'],
    )]
    private ?string $phone = null;

    #[ORM\Column(name: 'email', type: 'string', length: 50, nullable: false)]
    private string $email;

    #[ORM\Column(name: 'password', type: 'string', length: 60, nullable: false, options: ['fixed' => true])]
    private string $password;

    #[ORM\Column(name: 'name', type: 'string', length: 50, nullable: false)]
    private string $name;

    #[ORM\Column(name: 'surname', type: 'string', length: 50, nullable: false)]
    private string $surname;

    #[ORM\Column(
        name: 'opravneni',
        type: 'string',
        length: 100,
        nullable: false,
        options: ['comment' => 'oprávnění k jednotlivým modulům oddělená středníkem'],
    )]
    private string $permissions = '';

    #[ORM\Column(name: 'notifikace', type: 'boolean', nullable: false, options: ['default' => 0])]
    private bool $notificationsEnabled = false;

    #[ORM\Column(
        name: 'notify_days_before',
        type: 'integer',
        nullable: false,
        options: ['default' => 30, 'comment' => 'Počet dní před expirací'],
    )]
    private int $notificationLeadDays = 30;

    /** @var Collection<int, UserRole> */
    #[ORM\OneToMany(targetEntity: UserRole::class, mappedBy: 'user', cascade: [], orphanRemoval: false)]
    private Collection $userRoles;

    public function __construct()
    {
        $this->userRoles = new ArrayCollection();
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

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(?string $displayName): void
    {
        $this->displayName = $displayName;
    }

    public function getCompanyRegistrationNumber(): ?string
    {
        return $this->companyRegistrationNumber;
    }

    public function setCompanyRegistrationNumber(?string $companyRegistrationNumber): void
    {
        $this->companyRegistrationNumber = $companyRegistrationNumber;
    }

    public function getVatIdentificationNumber(): ?string
    {
        return $this->vatIdentificationNumber;
    }

    public function setVatIdentificationNumber(?string $vatIdentificationNumber): void
    {
        $this->vatIdentificationNumber = $vatIdentificationNumber;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(?string $street): void
    {
        $this->street = $street;
    }

    public function getStreetNumber(): ?string
    {
        return $this->streetNumber;
    }

    public function setStreetNumber(?string $streetNumber): void
    {
        $this->streetNumber = $streetNumber;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): void
    {
        $this->city = $city;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): void
    {
        $this->postalCode = $postalCode;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getSurname(): string
    {
        return $this->surname;
    }

    public function setSurname(string $surname): void
    {
        $this->surname = $surname;
    }

    public function getPermissions(): string
    {
        return $this->permissions;
    }

    public function setPermissions(string $permissions): void
    {
        $this->permissions = $permissions;
    }

    public function areNotificationsEnabled(): bool
    {
        return $this->notificationsEnabled;
    }

    public function setNotificationsEnabled(bool $notificationsEnabled): void
    {
        $this->notificationsEnabled = $notificationsEnabled;
    }

    public function getNotificationLeadDays(): int
    {
        return $this->notificationLeadDays;
    }

    public function setNotificationLeadDays(int $notificationLeadDays): void
    {
        $this->notificationLeadDays = $notificationLeadDays;
    }

    /** @return Collection<int, UserRole> */
    public function getUserRoles(): Collection
    {
        return $this->userRoles;
    }
}
