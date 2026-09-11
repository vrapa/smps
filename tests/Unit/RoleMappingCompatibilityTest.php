<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Model\entities\Role;
use App\Model\entities\UserRole;
use App\Model\entities\User;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

final class RoleMappingCompatibilityTest extends TestCase
{
    public function testRoleKeepsExistingTableAndColumnNames(): void
    {
        $table = (new ReflectionClass(Role::class))->getAttributes(Table::class)[0]->newInstance();
        $code = (new ReflectionProperty(Role::class, 'code'))->getAttributes(Column::class)[0]->newInstance();
        $description = (new ReflectionProperty(Role::class, 'description'))
            ->getAttributes(Column::class)[0]
            ->newInstance();

        self::assertSame('roles', $table->name);
        self::assertSame('kod', $code->name);
        self::assertSame('popis', $description->name);
    }

    public function testUserRoleKeepsExistingJoinTableAndForeignKeys(): void
    {
        $table = (new ReflectionClass(UserRole::class))->getAttributes(Table::class)[0]->newInstance();

        $roleProperty = new ReflectionProperty(UserRole::class, 'role');
        $roleJoin = $roleProperty->getAttributes(JoinColumn::class)[0]->newInstance();
        $roleRelation = $roleProperty->getAttributes(ManyToOne::class)[0]->newInstance();

        $userProperty = new ReflectionProperty(UserRole::class, 'user');
        $userJoin = $userProperty->getAttributes(JoinColumn::class)[0]->newInstance();
        $userRelation = $userProperty->getAttributes(ManyToOne::class)[0]->newInstance();

        self::assertSame('roles2users', $table->name);
        self::assertSame('roles_id', $roleJoin->name);
        self::assertFalse($roleJoin->nullable);
        self::assertNull($roleJoin->onDelete);
        self::assertSame(Role::class, $roleRelation->targetEntity);
        self::assertSame('users_id', $userJoin->name);
        self::assertFalse($userJoin->nullable);
        self::assertSame('CASCADE', $userJoin->onDelete);
        self::assertSame(User::class, $userRelation->targetEntity);
    }
}
