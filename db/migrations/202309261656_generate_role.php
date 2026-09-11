<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class GenerateRole extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->table('users')->hasColumn('role')) {
            return;
        }

        $users = $this->fetchAll('SELECT id, role FROM users');
        $data = [];

        foreach ($users as $user) {
            $roleId = match ($user['role']) {
                'admin' => 1,
                'user' => 2,
                default => 3,
            };

            $data[] = ['roles_id' => $roleId, 'users_id' => $user['id']];
        }

        if ($data !== []) {
            $this->table('roles2users')->insert($data)->save();
        }
    }

    public function down(): void
    {
        // The join rows are authoritative after migration and are not deleted.
    }
}
