<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class DropDeprecatedRole extends AbstractMigration
{
    public function up(): void
    {
        $users = $this->table('users');
        if ($users->hasColumn('deprecated_role')) {
            $users->removeColumn('deprecated_role')->update();
        }
    }

    public function down(): void
    {
        $users = $this->table('users');
        if (!$users->hasColumn('deprecated_role')) {
            $users->addColumn('deprecated_role', 'string', [
                'limit' => 100,
                'null' => false,
                'default' => '',
            ])->update();
        }
    }
}
