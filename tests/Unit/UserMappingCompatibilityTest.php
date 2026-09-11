<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Model\entities\User;
use App\Model\repositories\UserRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

final class UserMappingCompatibilityTest extends TestCase
{
    public function testEnglishPropertiesKeepExistingDatabaseIdentifiers(): void
    {
        $table = (new ReflectionClass(User::class))->getAttributes(Table::class)[0]->newInstance();
        $entity = (new ReflectionClass(User::class))->getAttributes(Entity::class)[0]->newInstance();
        self::assertSame('users', $table->name);
        self::assertSame(UserRepository::class, $entity->repositoryClass);

        $expectedColumns = [
            'username' => ['username', false],
            'displayName' => ['nazev', true],
            'companyRegistrationNumber' => ['ico', true],
            'vatIdentificationNumber' => ['dico', true],
            'street' => ['ulice', true],
            'streetNumber' => ['cislo_popisne', true],
            'city' => ['mesto', true],
            'postalCode' => ['psc', true],
            'phone' => ['telefon', true],
            'email' => ['email', false],
            'password' => ['password', false],
            'name' => ['name', false],
            'surname' => ['surname', false],
            'permissions' => ['opravneni', false],
            'notificationsEnabled' => ['notifikace', false],
            'notificationLeadDays' => ['notify_days_before', false],
        ];

        foreach ($expectedColumns as $propertyName => [$columnName, $nullable]) {
            $column = (new ReflectionProperty(User::class, $propertyName))
                ->getAttributes(Column::class)[0]
                ->newInstance();

            self::assertSame($columnName, $column->name, $propertyName);
            self::assertSame($nullable, $column->nullable, $propertyName);
        }
    }

    public function testPasswordHashStillFitsCurrentColumnLength(): void
    {
        $passwordColumn = (new ReflectionProperty(User::class, 'password'))
            ->getAttributes(Column::class)[0]
            ->newInstance();

        self::assertSame(60, $passwordColumn->length);
    }

    public function testNewUserHasSafeMappedDefaultsBeforePersistence(): void
    {
        $user = new User();

        self::assertFalse($user->hasId());
        self::assertNull($user->getDisplayName());
        self::assertNull($user->getCompanyRegistrationNumber());
        self::assertNull($user->getVatIdentificationNumber());
        self::assertNull($user->getStreet());
        self::assertNull($user->getStreetNumber());
        self::assertNull($user->getCity());
        self::assertNull($user->getPostalCode());
        self::assertNull($user->getPhone());
        self::assertSame('', $user->getPermissions());
        self::assertFalse($user->areNotificationsEnabled());
        self::assertSame(30, $user->getNotificationLeadDays());
    }

    public function testNotificationDefaultsMatchExistingSchema(): void
    {
        $notifications = (new ReflectionProperty(User::class, 'notificationsEnabled'))
            ->getAttributes(Column::class)[0]
            ->newInstance();
        $leadDays = (new ReflectionProperty(User::class, 'notificationLeadDays'))
            ->getAttributes(Column::class)[0]
            ->newInstance();

        self::assertSame(0, $notifications->options['default']);
        self::assertSame(30, $leadDays->options['default']);
    }
}
