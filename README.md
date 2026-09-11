# SMPS

Webová aplikace Smíšeného pěveckého sboru Bruntál pro správu skladeb,
koncertů, uživatelů a souvisejících souborů. Aplikace je postavená na Nette 3,
Doctrine ORM a PHP 8.1+.

Repozitář se připravuje na veřejné vydání. Aktuální stav a zbývající právní,
databázové a provozní kroky jsou v
[`docs/public-release-plan.md`](docs/public-release-plan.md). Dokud nejsou
splněné release gates, není repozitář připravený ke zveřejnění ani k nasazení
z čisté databáze.

## Požadavky

- PHP 8.1 nebo novější s PDO MySQL a rozšířeními požadovanými Composerem;
- Composer 2;
- MySQL nebo MariaDB;
- webový server s document rootem nastaveným na adresář `www`.

Produkční vstupní bod je `www/index.php`, konzolový vstupní bod `bin/console`.
Adresáře `temp` a `log` musí být zapisovatelné procesem PHP.

## Instalace závislostí

Po naklonování repozitáře spusťte v jeho kořenovém adresáři:

```sh
composer install
```

Verzovaný `composer.lock` zajišťuje opakovatelnou sadu závislostí. Změny
závislostí dělejte přes Composer a vždy commitněte odpovídající změnu locku.

## Lokální konfigurace

Zkopírujte bezpečný příklad a doplňte přístup k vlastní lokální databázi:

```sh
cp config/local.example.neon config/local.neon
```

Ve Windows PowerShellu použijte:

```powershell
Copy-Item config/local.example.neon config/local.neon
```

`config/local.neon` je ignorovaný a nesmí se commitovat. Totéž platí pro
`config/phinx.php`. Neukládejte do repozitáře produkční hostitele, uživatele,
hesla ani jiné tajné údaje.

Debug režim je ve výchozím stavu vypnutý. Pouze v důvěryhodném lokálním
prostředí jej lze pro daný proces zapnout proměnnou `SMPS_DEBUG=1`.

## Databáze a migrace

Phinx migrace obsahují kompletní počáteční schéma pro prázdnou databázi.
Počáteční migrace používá verzi, kterou už mají existující instalace zapsanou,
takže se na nich znovu nespustí; navazující historické migrace současně umějí
bezpečně převést starý sloupec `users.role`. Návrh a lokální ověření jsou v
[`docs/database-inventory.md`](docs/database-inventory.md).

Pro práci s již kompatibilní lokální databází zkopírujte a upravte příklad:

```sh
cp config/phinx.example.php config/phinx.php
php vendor/bin/phinx status -e development -c config/phinx.php
php vendor/bin/phinx migrate -e development -c config/phinx.php
```

Pro jednorázovou testovací databázi zkopírujte také
`config/test.example.neon` do ignorovaného `config/test.neon`, nastavte účet
omezený na databázi s názvem končícím `_test` a použijte výslovně testovací
prostředí:

```sh
php vendor/bin/phinx migrate -e testing -c config/phinx.php
SMPS_ENV=test php bin/console orm:validate-schema
SMPS_ENV=test SMPS_DATABASE_TESTS=1 php vendor/bin/phpunit tests/Integration
```

Databázové testy se bez `SMPS_DATABASE_TESTS=1` přeskočí a jejich konfigurace
musí ukazovat na databázi s názvem končícím `_test`. CI vytváří tuto databázi
od nuly v samostatné službě MariaDB 10.11.

Před každou migrací ověřte zvolený hostitel, databázi a prostředí. Migrace,
rollback, generování schématu ani reset databáze nespouštějte proti produkci
bez samostatné zálohy, revize a výslovného schválení. Migrace jsou dostupné
jen přes CLI; aplikace nemá webový migrační endpoint.

## Spuštění aplikace

Nastavte document root Apache/Nginx na `www` a nechte server směrovat požadavky
podle `www/.htaccess` nebo ekvivalentního pravidla pro front controller. Po
nakonfigurování kompatibilní databáze lze dostupné konzolové příkazy zobrazit:

```sh
php bin/console list
```

Uživatelské uploady se ukládají do ignorovaného `www/dokumenty`. Tento adresář
je runtime data, nikoli součást zdrojového kódu; nesmí se čistit při instalaci
ani nasazení.

Sborové fotografie pro úvodní carousel se spravují mimo repozitář v
`www/images/carousel`. Čistá instalace je musí získat samostatnou, oprávněnou
cestou. Nasazení tento adresář nepřenáší ani nemaže.

## Testy a kontroly kvality

Aktuální PHPUnit testy jsou izolované unit/kompatibilitní testy a nevyžadují
databázové připojení:

```sh
php vendor/bin/phpunit
```

Lokální kontrolní sada odpovídající verzovaným konfiguracím:

```sh
composer validate --strict --no-check-publish
composer audit --locked
php vendor/bin/phpcs
php vendor/bin/phpstan analyse --configuration phpstan.neon --memory-limit=512M
php vendor/bin/latte-lint app
php vendor/bin/neon-lint config
```

PHPStan je zatím nastavený na úroveň 0 nad aplikací, testy, CLI, migracemi a
webovým vstupním bodem. Vyšší úroveň je žádoucí další krok, nemá však být
nahrazena rozsáhlým ignorováním chyb.

## Architektura

- `app/Modules/Presenters` a `app/Modules/Admin/Presenters` obsahují Nette
  presentery; Latte šablony jsou v odpovídajících adresářích `templates`.
- `app/Model/entities` obsahuje Doctrine entity mapované PHP atributy a
  `app/Model/repositories` jejich repozitáře.
- `app/Services` obsahuje znovupoužitelnou aplikační a transakční logiku.
- `config/common.neon`, `config/services.neon` a ignorovaný
  `config/local.neon` skládají runtime DI konfiguraci.
- `db/migrations` obsahuje Phinx migrace; Phinx spravuje schéma, není ORM.
- `www` je jediný veřejný adresář a obsahuje vstupní bod a statické assety.

Persistence aplikačního kódu používá Doctrine ORM. Po zápisu entit je nutné
zachovat stávající transakční hranice a zavolat `flush()`. Přímé databázové
operace patří do migrací nebo do výslovně zdokumentovaných integračních míst.

## Nasazení

Stávající GitLab pipeline je dočasná a může nasazovat větev `master` do
produkce. Veřejný release plán ji nahrazuje testovaným GitHub artefaktem a
bezpečným no-delete přenosem popsaným v `docs/deployment.md`. Neměňte deploy
cíle, nespouštějte produkční migrace a nezveřejňujte repozitář, dokud nejsou
splněné příslušné body plánu.

## Licence

Zdrojový kód projektu je dostupný pod [licencí MIT](LICENSE), Copyright (c)
2026 Radovan Kraus. Licence distribuovaných knihoven jsou uvedené v
[`THIRD_PARTY_NOTICES.md`](THIRD_PARTY_NOTICES.md).
