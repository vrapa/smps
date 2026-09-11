<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateInitialSchema extends AbstractMigration
{
    public function up(): void
    {
        $this->execute(<<<'SQL'
CREATE TABLE `users` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `username` varchar(20) COLLATE utf8mb3_czech_ci NOT NULL COMMENT 'login',
  `nazev` varchar(90) COLLATE utf8mb3_czech_ci DEFAULT NULL COMMENT 'nazev firmy',
  `ico` varchar(8) COLLATE utf8mb3_czech_ci DEFAULT NULL COMMENT 'IČO',
  `dico` varchar(10) COLLATE utf8mb3_czech_ci DEFAULT NULL COMMENT 'DIČO',
  `ulice` varchar(50) COLLATE utf8mb3_czech_ci DEFAULT NULL COMMENT 'ulice',
  `cislo_popisne` varchar(15) COLLATE utf8mb3_czech_ci DEFAULT NULL COMMENT 'cp',
  `mesto` varchar(40) COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `psc` varchar(5) COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `telefon` varchar(20) COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `email` varchar(50) NOT NULL,
  `password` char(60) NOT NULL,
  `name` varchar(50) NOT NULL,
  `surname` varchar(50) NOT NULL,
  `opravneni` varchar(100) NOT NULL COMMENT 'oprávnění k jednotlivým modulům oddělená středníkem',
  `notifikace` tinyint(1) NOT NULL DEFAULT 0,
  `notify_days_before` int(11) NOT NULL DEFAULT 30 COMMENT 'Počet dní před expirací',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username_2` (`username`),
  UNIQUE KEY `username` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kod` varchar(30) COLLATE utf8mb4_czech_ci NOT NULL,
  `popis` text COLLATE utf8mb4_czech_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

INSERT INTO `roles` (`id`, `kod`, `popis`) VALUES
  (1, 'admin', 'Může dělat v aplikaci vše.'),
  (2, 'user', 'Má všechna práva jako guest (jen čtení).'),
  (3, 'guest', 'Osoba má jen práva čtenáře, nemůže nic upravovat, vytvářet ani nahrávat.');

CREATE TABLE `skladby` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nazev` varchar(40) COLLATE utf8mb3_czech_ci NOT NULL,
  `autor` varchar(40) COLLATE utf8mb3_czech_ci NOT NULL,
  `active` tinyint(1) NOT NULL,
  `createdAt` datetime NOT NULL,
  `createdBy` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `createdBy` (`createdBy`),
  CONSTRAINT `skladby_ibfk_1` FOREIGN KEY (`createdBy`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

CREATE TABLE `koncerty` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nazev` varchar(40) COLLATE utf8mb3_czech_ci NOT NULL,
  `kdy` datetime NOT NULL,
  `poznamka` text COLLATE utf8mb3_czech_ci NOT NULL,
  `createdAt` datetime NOT NULL,
  `createdBy` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `createdBy` (`createdBy`),
  CONSTRAINT `koncerty_ibfk_1` FOREIGN KEY (`createdBy`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

CREATE TABLE `roles2users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `roles_id` int(11) NOT NULL,
  `users_id` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `roles_id` (`roles_id`),
  KEY `users_id` (`users_id`),
  CONSTRAINT `roles2users_ibfk_1` FOREIGN KEY (`roles_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `roles2users_ibfk_2` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE `skladby2koncerty` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `skladby_id` int(11) NOT NULL,
  `koncerty_id` int(11) NOT NULL,
  `priority` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `skladby_id` (`skladby_id`),
  KEY `koncerty_id` (`koncerty_id`),
  CONSTRAINT `skladby2koncerty_ibfk_1` FOREIGN KEY (`skladby_id`) REFERENCES `skladby` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `skladby2koncerty_ibfk_2` FOREIGN KEY (`koncerty_id`) REFERENCES `koncerty` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

CREATE TABLE `soubory2skladby` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `skladby_id` int(11) NOT NULL,
  `kategorie` varchar(40) COLLATE utf8mb4_czech_ci NOT NULL,
  `filename` varchar(150) COLLATE utf8mb4_czech_ci NOT NULL,
  `popis` varchar(150) COLLATE utf8mb4_czech_ci NOT NULL,
  `createdAt` datetime NOT NULL,
  `createdBy` int(10) NOT NULL,
  `priorita` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `skladby_id` (`skladby_id`),
  KEY `createdBy` (`createdBy`),
  CONSTRAINT `soubory2skladby_ibfk_1` FOREIGN KEY (`skladby_id`) REFERENCES `skladby` (`id`) ON DELETE CASCADE,
  CONSTRAINT `soubory2skladby_ibfk_2` FOREIGN KEY (`createdBy`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;
SQL);
    }

    public function down(): void
    {
        foreach ([
            'soubory2skladby',
            'skladby2koncerty',
            'roles2users',
            'koncerty',
            'skladby',
            'roles',
            'users',
        ] as $table) {
            if ($this->hasTable($table)) {
                $this->table($table)->drop()->save();
            }
        }
    }
}
