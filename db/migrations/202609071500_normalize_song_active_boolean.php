<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class NormalizeSongActiveBoolean extends AbstractMigration
{
    public function up(): void
    {
        $this->execute(<<<'SQL'
ALTER TABLE `skladby`
  MODIFY `active` TINYINT(1) NOT NULL
SQL);
    }

    public function down(): void
    {
        $this->execute(<<<'SQL'
ALTER TABLE `skladby`
  MODIFY `active` BIT(1) NOT NULL
SQL);
    }
}
