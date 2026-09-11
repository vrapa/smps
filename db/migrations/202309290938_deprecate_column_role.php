<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class DeprecateColumnRole extends AbstractMigration
{
    public function up(): void
    {
        $users = $this->table('users');
        if ($users->hasColumn('role') && !$users->hasColumn('deprecated_role')) {
            $users->renameColumn('role', 'deprecated_role')->update();
        }
    }

    public function down(): void
    {
        $users = $this->table('users');
        if ($users->hasColumn('deprecated_role') && !$users->hasColumn('role')) {
            $users->renameColumn('deprecated_role', 'role')->update();
        }
    }
}
