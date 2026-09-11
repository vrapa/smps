# Repository guide for coding agents

## Project overview

- This is a small Nette 3 web application for SMPS (Smiseny pevecky sbor Bruntal).
- The application requires PHP 8.1 or newer; GitLab CI currently runs PHP 8.3.
- The web entry point is `www/index.php`. The CLI entry point is `bin/console`.
- `App\Bootstrap` builds the Nette container. Runtime configuration is assembled from `config/common.neon`, `config/services.neon`, and the ignored `config/local.neon`.
- Presenter routing follows `App\Modules\*\Presenters\*Presenter`. Latte templates live under `app/Modules/templates` and `app/Modules/Admin/templates`.

## Important directories

- `app/Modules/Presenters` and `app/Modules/Admin/Presenters`: HTTP presenters and form handlers.
- `app/Model/entities`: Doctrine entities mapped with PHP 8 attributes.
- `app/Model/repositories`: custom Doctrine repositories derived from `AbstractRepository`/`Doctrine\ORM\EntityRepository`.
- `app/Services`: application services. Some deliberately use both Doctrine and Nette Database.
- `config`: Nette and Doctrine configuration. Local credentials are not versioned.
- `db/migrations`: Phinx migrations.
- `www`: public document root and static assets.
- `www/dokumenty`: runtime user uploads; it is ignored by Git and must not be deleted or treated as source code.
- `temp` and `log`: writable Nette runtime data; both are ignored by Git.

## Database layers

The project uses two database access styles. Do not assume that every query goes through an ORM.

1. **Doctrine ORM is the primary ORM.** It is integrated into Nette through Nettrine (`nettrine/orm`). Entities use `Doctrine\ORM\Mapping` PHP attributes and are discovered under `app/Model`. Inject `Doctrine\ORM\EntityManagerInterface` for entity persistence and use the existing custom repositories for entity queries.
2. **Nette Database Explorer is also used directly.** `Nette\Database\Explorer` appears in authentication, services, and presenters for Selection/ActiveRow operations and raw parameterized SQL. It is a database abstraction/query layer, not a second ORM. Preserve the access style of the code being changed unless the task explicitly calls for a broader migration.
3. **Phinx manages schema migrations.** It is not an ORM. Migration files are in `db/migrations`; local connection settings are in ignored `config/phinx.php` with `config/phinx.example.php` as the template.

Nettrine is the Nette integration for Doctrine, not a separate ORM. Doctrine DBAL is configured by Nettrine underneath Doctrine ORM. Phinx brings CakePHP Database transitively, but application code does not use it as an ORM.

When changing persistence code:

- Keep entity mappings, database nullability, and PHP property types consistent.
- Remember to call `flush()` after Doctrine writes at the existing transaction boundary.
- Continue using parameterized calls when working with `Nette\Database\Explorer`; never concatenate user input into SQL.
- Avoid mixing Doctrine-managed entity writes and direct SQL writes in one operation unless cache/identity-map consistency is handled explicitly.
- Do not run migrations, rollbacks, schema generation, or database-reset commands without explicit user authorization.
- Database migrations must be run through the CLI. Do not add a web-accessible migration or rollback endpoint.

## Local configuration and secrets

- `config/local.neon` and `config/phinx.php` are ignored and may contain real credentials. Use the corresponding `*.example.*` files as templates. The legacy ignored `config/phinx.yaml` may still exist locally but requires the optional `symfony/yaml` parser; use the PHP configuration for dependency-free Phinx commands.
- Never commit, overwrite, print, or copy secrets from local configuration into documentation, tests, logs, or patches.
- `composer.lock` is currently ignored and not tracked. Be aware that `composer install` may resolve newer allowed versions. Do not change dependencies unless the task requires it.

## Development and validation

Install dependencies with:

```sh
composer install
```

Use the narrowest relevant checks while developing. The checks represented in CI are:

```sh
vendor/bin/phpcs --standard=PSR1 app
vendor/bin/phpstan analyse --level=0 --memory-limit=512M app
vendor/bin/latte-lint app
vendor/bin/neon-lint config
```

For a more representative local static-analysis run, use the repository configuration:

```sh
vendor/bin/phpstan analyse --configuration phpstan.neon --memory-limit=512M
vendor/bin/phpcs
```

PHPUnit tests live under `tests/` and run with `php vendor/bin/phpunit`. For PHP changes, at minimum lint every changed PHP file with `php -l`, run the narrowest relevant PHPUnit tests, and run the relevant quality checks above. Do not silently apply broad formatter or Rector changes to unrelated legacy code.

## Code conventions

- Follow the style and namespace structure of neighboring files; this is a legacy codebase with some inconsistent casing and type coverage.
- Prefer focused changes over opportunistic refactors.
- New PHP files should use `declare(strict_types=1);` where compatible with surrounding code.
- Keep presenters thin when practical; put reusable domain/database behavior in services or repositories.
- Preserve Czech UI text and existing terminology unless the requested change includes copy editing.
- Do not edit generated/vendor code or static third-party libraries under `vendor` and `www`.

## Deployment safety

- `.gitlab-ci.yml` deploys automatically from `master` after quality stages.
- Production deployment uses reverse `lftp mirror` with `-e`/`--delete` for `app`, `db`, `config`, and `vendor`. Any remote-only file inside those directories can be deleted during deployment.
- `temp/cache` is explicitly removed after deployment. Other runtime data, especially `www/dokumenty`, must remain outside mirrored directories.
- The deployment only uploads selected files under `www`; it does not mirror the entire public directory.
- The text "Deploying db migrate" only copies migration files; the current pipeline does not execute Phinx migrations.
- There is intentionally no web-accessible Phinx endpoint.
- Do not trigger a deployment, modify FTP destinations, weaken exclusions, or add destructive synchronization/migration steps without explicit user authorization. Treat changes to `.gitlab-ci.yml`, migration files, ignored runtime paths, and upload storage as high risk.

## Agent workflow

- Before editing, inspect `git status` and preserve unrelated user changes.
- Search with `rg`/`rg --files` and inspect the nearest presenter, service, repository, entity, template, and configuration involved.
- Keep changes scoped, validate them proportionally, and report checks that could not be run.
- Never delete production-like data, user uploads, local configuration, caches, or ignored files merely to make a clean working tree.
