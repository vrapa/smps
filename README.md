# SMPS

[English](#english) | [Česky](#cesky)

<a id="english"></a>

## English

SMPS is a self-hosted web application for managing a choir's song catalogue,
concert programmes, users, roles, and related files. It was originally built
for the Mixed Choir of Bruntál, Czech Republic, but its core workflow is not
tied to that organisation and can be used or adapted by choirs anywhere in the
world.

The application uses Nette 3, Doctrine ORM, Phinx, and PHP 8.1 or newer. The
The user interface supports Czech, English, German, and Dutch. Each installation
selects one language and an IANA timezone in configuration; user-created song,
concert, and file content remains exactly as entered. Translation acceptance
and review status are tracked in
[`docs/localization-plan.md`](docs/localization-plan.md).

### Requirements and installation

- PHP 8.1 or newer, with PDO MySQL, Intl, and Composer-required extensions;
- Composer 2;
- MySQL or MariaDB;
- a web server whose document root points to `www`.

The web entry point is `www/index.php`; the CLI entry point is `bin/console`.
The PHP process must be able to write to `temp` and `log`. Install the locked,
reproducible dependency set in a clean clone with:

```sh
composer install
```

### Local configuration

Copy the safe example and add credentials for your own local database:

```sh
cp config/local.example.neon config/local.neon
```

On Windows PowerShell:

```powershell
Copy-Item config/local.example.neon config/local.neon
```

`config/local.neon` and `config/phinx.php` are ignored and must never be
committed. Do not store production hosts, usernames, passwords, private keys,
or other secrets in this repository. Debug mode is disabled by default; set
`SMPS_DEBUG=1` only in a trusted local development environment.

The installation-wide UI language is selected by `parameters.locale` (`cs`,
`en`, `de`, or `nl`). Set `parameters.timezone` to an IANA timezone such as
`Europe/Prague` or `Europe/Amsterdam`; it controls concert input and display.

### Database and migrations

Phinx migrations contain the complete schema for a fresh database. The
baseline uses a version already recorded by existing installations, so it is
not replayed there; guarded follow-up migrations also support upgrades from
the former `users.role` column. See
[`docs/database-inventory.md`](docs/database-inventory.md).

For a compatible local database, copy and edit the example configuration:

```sh
cp config/phinx.example.php config/phinx.php
php vendor/bin/phinx status -e development -c config/phinx.php
php vendor/bin/phinx migrate -e development -c config/phinx.php
```

For integration tests, also copy `config/test.example.neon` to the ignored
`config/test.neon` and use a disposable database whose name ends in `_test`:

```sh
php vendor/bin/phinx migrate -e testing -c config/phinx.php
SMPS_ENV=test php bin/console orm:validate-schema
SMPS_ENV=test SMPS_DATABASE_TESTS=1 php vendor/bin/phpunit tests/Integration
```

Database tests are skipped unless `SMPS_DATABASE_TESTS=1` is set. CI builds a
fresh isolated database with MariaDB 10.11. Always verify the selected host,
database, and environment first. Never run migrations, rollbacks, schema
generation, or database resets against production without a separate backup,
review, and explicit approval. Migrations are CLI-only.

### Running and runtime content

Point Apache or Nginx at `www` and route requests according to
`www/.htaccess` or an equivalent front-controller rule. List CLI commands with:

```sh
php bin/console list
```

User uploads are stored at runtime in the ignored `www/dokumenty` directory.
Choir photographs for the home-page carousel are managed outside the
repository in ignored `www/images/carousel`. These deployment-excluded runtime
directories must not be deleted during installation or deployment. The
carousel discovers top-level JPEG, PNG, and WebP files at runtime; when the
directory is absent or empty, the dashboard shows a localized welcome instead
of broken image links.

### Public repository content and privacy

The public source tree and public Git history contain no choir photographs,
user uploads, database dumps, sheet music, scores, PDFs, office documents,
MIDI files, or audio recordings. They also contain no production credentials
or member data. Tracked binary images are limited to third-party UI icon
sprites and the application favicon; licences are documented in
[`THIRD_PARTY_NOTICES.md`](THIRD_PARTY_NOTICES.md).

Do not commit content from `www/dokumenty` or `www/images/carousel`. Before
adding media or personal data, confirm publication permission and licence
compatibility.

### Tests and quality checks

```sh
php vendor/bin/phpunit
composer validate --strict --no-check-publish
composer audit --locked
php vendor/bin/phpcs
php vendor/bin/phpstan analyse --configuration phpstan.neon --memory-limit=512M
php vendor/bin/latte-lint app
php vendor/bin/neon-lint config
python tools/audit_public_content.py source --history
python -m unittest discover -s tools/tests -p 'test_*.py'
```

PHPStan currently runs at level 0 over application code, tests, CLI code,
migrations, and the web entry point. Raising the level remains a future
improvement and should not be replaced by broadly ignoring errors.

When adding user-facing copy, use a semantic translation key instead of a raw
sentence in a presenter, form, or template. Add the same key and placeholders
to every catalogue under `app/Lang/{cs,en,de,nl}`. PHPUnit checks catalogue
parity and rejects new untranslated UI text in the audited application paths.

Tests and examples must use visibly synthetic identities, the reserved
`example.test` email domain, generated marker content instead of real documents
or recordings, and no production password hashes. The test-data privacy audit
enforces the mechanically verifiable parts of this rule.

### Architecture

- `app/Modules` contains Nette presenters and Latte templates.
- `app/Model/entities` and `app/Model/repositories` contain Doctrine entities
  and repositories.
- `app/Services` contains reusable application and transaction logic.
- `config` contains public configuration and ignored local configuration.
- `db/migrations` contains Phinx schema migrations; Phinx is not an ORM.
- `www` is the only public directory and contains the entry point and assets.

Application persistence uses Doctrine ORM. Entity writes must preserve the
existing transaction boundaries and call `flush()`.

### Deployment

GitHub Actions builds a tested artifact and provides a separate, manually
approved production workflow. Its protected environment and least-privilege
SFTP credentials must be configured before use. Deployment is designed not to
transfer or delete local configuration, uploads, photographs, logs, temporary
data, or database data. See [`docs/deployment.md`](docs/deployment.md).

GitHub is the only active source for development, issues, and pull requests.
The historical GitLab deployment is a restricted transition fallback: freeze
it before the first GitHub production run and retire it after the new workflow
and recovery procedure are verified. Do not change targets or run production
migrations without explicit operational approval.

### Licence

The project source is available under the [MIT License](LICENSE), Copyright
(c) 2026 Radovan Kraus. Third-party licences are listed in
[`THIRD_PARTY_NOTICES.md`](THIRD_PARTY_NOTICES.md).

<a id="cesky"></a>

## Česky

SMPS je samostatně hostovaná webová aplikace pro správu katalogu skladeb,
programů koncertů, uživatelů, rolí a souvisejících souborů pěveckého sboru.
Původně vznikla pro Smíšený pěvecký sbor Bruntál, její základní postupy ale
nejsou vázané na tuto organizaci a mohou ji používat nebo upravit sbory kdekoli
na světě.

Aplikace používá Nette 3, Doctrine ORM, Phinx a PHP 8.1 nebo novější. Uživatelské
rozhraní podporuje češtinu, angličtinu, němčinu a nizozemštinu. Každá instalace
vybírá jeden jazyk a IANA časovou zónu v konfiguraci; uživatelské názvy skladeb,
koncertů a souborů zůstávají beze změny. Stav akceptace a jazykové kontroly je v
[`docs/localization-plan.md`](docs/localization-plan.md).

### Požadavky a instalace

- PHP 8.1 nebo novější s PDO MySQL, Intl a rozšířeními požadovanými Composerem;
- Composer 2;
- MySQL nebo MariaDB;
- webový server s document rootem nastaveným na `www`.

Webový vstupní bod je `www/index.php`, CLI vstupní bod je `bin/console`.
Adresáře `temp` a `log` musí být zapisovatelné procesem PHP. Závislosti
nainstalujete z verzovaného locku příkazem:

```sh
composer install
```

### Lokální konfigurace

```sh
cp config/local.example.neon config/local.neon
```

Ve Windows PowerShellu:

```powershell
Copy-Item config/local.example.neon config/local.neon
```

`config/local.neon` a `config/phinx.php` jsou ignorované a nesmějí se
commitovat. Neukládejte sem produkční hostitele, uživatele, hesla, soukromé
klíče ani jiná tajemství. Debug režim je ve výchozím stavu vypnutý;
`SMPS_DEBUG=1` používejte jen v důvěryhodném lokálním prostředí.

Jazyk celé instalace vybírá `parameters.locale` (`cs`, `en`, `de` nebo `nl`).
V `parameters.timezone` nastavte IANA časovou zónu, například `Europe/Prague`
nebo `Europe/Amsterdam`; používá se při zadávání a zobrazení koncertů.

### Databáze a migrace

Phinx migrace obsahují kompletní schéma pro prázdnou databázi. Počáteční
migrace používá verzi již zapsanou v existujících instalacích; chráněné
navazující migrace podporují i přechod ze starého `users.role`. Podrobnosti
jsou v [`docs/database-inventory.md`](docs/database-inventory.md).

```sh
cp config/phinx.example.php config/phinx.php
php vendor/bin/phinx status -e development -c config/phinx.php
php vendor/bin/phinx migrate -e development -c config/phinx.php
```

Pro integrační testy zkopírujte také `config/test.example.neon` do ignorovaného
`config/test.neon` a použijte jednorázovou databázi s názvem končícím `_test`:

```sh
php vendor/bin/phinx migrate -e testing -c config/phinx.php
SMPS_ENV=test php bin/console orm:validate-schema
SMPS_ENV=test SMPS_DATABASE_TESTS=1 php vendor/bin/phpunit tests/Integration
```

Databázové testy se bez `SMPS_DATABASE_TESTS=1` přeskočí. Před migrací vždy
ověřte hostitele, databázi a prostředí. Migrace, rollback, generování schématu
ani reset databáze nespouštějte proti produkci bez samostatné zálohy, revize a
výslovného schválení. Migrace jsou dostupné jen přes CLI.

### Spuštění a runtime obsah

Nastavte Apache nebo Nginx na adresář `www` a směrujte požadavky podle
`www/.htaccess` nebo ekvivalentního pravidla. CLI příkazy zobrazíte takto:

```sh
php bin/console list
```

Uživatelské uploady se ukládají do ignorovaného `www/dokumenty`. Sborové
fotografie pro carousel se spravují mimo repozitář v ignorovaném
`www/images/carousel`. Jde o runtime data vyloučená z nasazení a při instalaci
ani nasazení se nesmějí mazat. Carousel za běhu načte soubory JPEG, PNG a WebP
přímo z tohoto adresáře; pokud adresář chybí nebo je prázdný, dashboard zobrazí
lokalizované uvítání namísto nefunkčních obrázků.

### Veřejný obsah a soukromí

Veřejný zdrojový strom ani veřejná Git historie neobsahují sborové fotografie,
uživatelské uploady, databázové exporty, noty, partitury, PDF, kancelářské
dokumenty, MIDI ani zvukové nahrávky. Neobsahují ani produkční přístupové údaje
nebo data členů. Verzované binární obrázky jsou jen ikonové sprity třetích
stran a favicon; licence popisuje
[`THIRD_PARTY_NOTICES.md`](THIRD_PARTY_NOTICES.md).

Obsah z `www/dokumenty` ani `www/images/carousel` necommitujte. Před přidáním
média nebo osobního údaje ověřte oprávnění ke zveřejnění a slučitelnost licence.

### Testy a kontroly kvality

```sh
php vendor/bin/phpunit
composer validate --strict --no-check-publish
composer audit --locked
php vendor/bin/phpcs
php vendor/bin/phpstan analyse --configuration phpstan.neon --memory-limit=512M
php vendor/bin/latte-lint app
php vendor/bin/neon-lint config
python tools/audit_public_content.py source --history
python -m unittest discover -s tools/tests -p 'test_*.py'
```

PHPStan je zatím na úrovni 0 nad aplikací, testy, CLI, migracemi a webovým
vstupem. Zvýšení úrovně je žádoucí budoucí krok.

Nové texty uživatelského rozhraní zapisujte přes významový překladový klíč,
nikoli jako přímou větu v presenteru, formuláři nebo šabloně. Stejný klíč a
zástupné symboly doplňte do všech katalogů v `app/Lang/{cs,en,de,nl}`. PHPUnit
kontroluje shodu katalogů a v auditovaných částech aplikace odmítne nové
nepřeložené texty.

Testy a příklady musí používat zjevně syntetické identity, vyhrazenou e-mailovou
doménu `example.test`, generovaný značkovací obsah místo skutečných dokumentů či
nahrávek a žádné produkční hashe hesel. Mechanicky ověřitelnou část tohoto
pravidla hlídá audit testovacích dat.

### Architektura

- `app/Modules` obsahuje Nette presentery a Latte šablony.
- `app/Model/entities` a `app/Model/repositories` obsahují Doctrine entity a
  repozitáře.
- `app/Services` obsahuje znovupoužitelnou aplikační a transakční logiku.
- `config` obsahuje veřejnou a ignorovanou lokální konfiguraci.
- `db/migrations` obsahuje Phinx migrace schématu; Phinx není ORM.
- `www` je jediný veřejný adresář a obsahuje vstupní bod a assety.

Persistence aplikace používá Doctrine ORM. Zápisy entit musí zachovat stávající
transakční hranice a volat `flush()`.

### Nasazení

GitHub Actions sestavuje otestovaný artefakt a nabízí oddělený, ručně
schvalovaný produkční workflow. Před použitím je nutné nastavit chráněné
produkční prostředí a SFTP přístup s minimálními právy. Nasazení nepřenáší ani
nemaže lokální konfiguraci, uploady, fotografie, logy, dočasná či databázová
data. Viz [`docs/deployment.md`](docs/deployment.md).

GitHub je jediným aktivním zdrojem pro vývoj, issues a pull requesty. Historický
GitLab deployment je jen omezená přechodová záloha: před prvním produkčním
nasazením z GitHubu jej zmrazte a po ověření nového workflow a obnovy jej
vyřaďte. Bez výslovného schválení neměňte cíle nasazení ani nespouštějte
produkční migrace.

### Licence

Zdrojový kód je dostupný pod [licencí MIT](LICENSE), Copyright (c) 2026 Radovan
Kraus. Licence třetích stran uvádí
[`THIRD_PARTY_NOTICES.md`](THIRD_PARTY_NOTICES.md).
