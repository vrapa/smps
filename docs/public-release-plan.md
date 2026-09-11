# Public release plan

This document is the execution plan for preparing SMPS for a public GitHub
repository under the MIT License. Complete each section in a separate,
reviewable commit or pull request. Do not publish the repository until the
release gate at the end passes.

## Decisions

- Source code will be published on GitHub under the MIT License.
- Production users, usernames, password hashes, uploads, and database data
  must be preserved.
- Doctrine ORM will become the only application database access layer.
- Source-code identifiers, comments, and developer-facing messages will use
  English. Czech UI copy remains Czech.
- Production deployment will move from GitLab to GitHub Actions and use
  FTPS or SFTP. Plain FTP is a last resort and is not the target design.
- Deployment must never overwrite or delete production-only configuration,
  uploads, logs, temporary data, or database data.

## 1. Security and public-repository cleanup

- [x] Remove hard-coded production or personal debug settings.
- [x] Remove commented or active plaintext passwords, tokens, and credentials.
- [x] Remove obsolete deployment files, including `.gitlab-ci.yml.old`.
- [x] Remove the unrelated PHPStan baseline and obsolete `src/Erudio`
  configuration; regenerate a project-specific baseline only when needed.
- [x] Run a secret scan over the current source tree and Git history. See
  `docs/public-release-audit.md` for scope, findings, and limitations.
- [x] Remove choir photographs from version control and keep their
  production-managed directory out of source and deployment artifacts.
- [x] Review remaining names, email addresses, and other personal data for
  permission to publish.
- [x] Verify that the public tree and public history contain no sheet music,
  scores, PDFs, office documents, MIDI/audio recordings, database exports,
  choir photographs, or user uploads.
- [x] Ensure `config/local.neon`, `config/phinx.php`, legacy
  `config/phinx.yaml`, uploads, logs, and
  temporary files remain ignored and are never copied into documentation or
  tests.

Done when the current tree has no publishable secrets or production-only data.

Current status: complete. The three choir photographs have been removed from
version control; `www/images/carousel` is ignored and excluded from deploy
artifacts while existing production files remain untouched. No first-party
private email remains in the tracked tree. `Radovan Kraus` is intentionally
public in the MIT notice, and public commits use GitHub's
`28861508+vrapa@users.noreply.github.com` address. The private GitLab history,
including its original commit identity and removed footer blob, will not be
published. A post-publication content audit on 2026-09-11 found no private
content or music documents in the 165-file public tree. Checksum-verified
Gitleaks 8.30.1 and GitHub CI also reported no leaks in the selected public
history.

## 2. MIT License and repository metadata

- [x] Confirm the copyright holder and year: 2026, Radovan Kraus.
- [x] Add a standard root `LICENSE` file containing the MIT License.
- [x] Change `composer.json` to use the SPDX identifier `MIT`.
- [x] Add a license section to `README.md`.
- [x] Preserve notices for vendored third-party assets and add
  `THIRD_PARTY_NOTICES.md` if needed.
- [x] Add `SECURITY.md` and `CONTRIBUTING.md`.

Done when the repository clearly states the MIT License without claiming that
third-party dependencies are relicensed.

Current status: complete. The root `LICENSE` contains the standard MIT License
with `Copyright (c) 2026 Radovan Kraus`; Composer and README metadata identify
the same project license, while third-party licensing remains documented
separately in `THIRD_PARTY_NOTICES.md`.

## 3. Test baseline and database inventory

- [x] Create a dedicated MySQL/MariaDB test database configuration. See
  `config/test.example.neon`, the `testing` Phinx environment, and
  `docs/database-inventory.md`.
- [x] Add integration tests for login, roles, users, songs, concerts, files,
  pagination, and form defaults.
- [x] Test existing password-hash compatibility with representative test data;
  never copy production hashes or credentials into tests.
- [x] Record the actual production schema and compare it with Doctrine mapping.
- [x] Create a safe baseline strategy for fresh installations without replaying
  a new initial migration against the production database.

Done when the main application behaviour is covered before persistence changes.

Current status: repository mappings and migration coverage are inventoried, and
the migration chain now builds all seven mapped tables on an empty database.
The already-applied first migration version contains the complete baseline, so
existing installations do not replay it; guarded historical migrations still
support upgrades from `users.role`. A disposable local `smps_test` database
passed migrate, full rollback, migrate, and Doctrine schema validation. An
authorized structure-only production export was reviewed on 2026-09-07. It
contained exactly the seven mapped tables plus `_phinxlog` and no data,
triggers, routines, events, or definers. The comparison found the expected
pending `users.deprecated_role` removal and one additional difference:
production stores `skladby.active` as `BIT(1)`, while Doctrine DBAL uses
`TINYINT(1)` for the mapped boolean. A dedicated reversible migration now
normalizes that column. PHPUnit verifies representative bcrypt hashes without
production credentials or hashes. `docs/database-inventory.md` records the
comparison without copying the raw export into version control.

The first database-backed application scenario now persists a user with a
seeded role, verifies repository count, pagination, email lookup and role
choices, rejects an invalid password, and authenticates the valid credentials
with the expected identity and role. The test runs inside a transaction and
rolls back all inserted rows. A second transactional scenario covers song and
concert persistence, repository choices and pagination, song-file metadata,
concert repertoire synchronization, and concert deletion. It also leaves all
affected tables empty. A presenter-level scenario then runs real Nette GET
requests for song, concert, user, and authentication presenters. It verifies
admin authorization, database-backed edit defaults and choices, and the login
form including CSRF protection. Together these scenarios cover every area in
the integration checklist without retaining test rows or upload files.

## 4. English domain language

- [x] Create and document a single domain glossary before renaming classes.
  See `docs/domain-glossary.md`.
- [x] Rename PHP classes, methods, properties, variables, constants, services,
  repositories, form keys, template variables, PHPDoc, and developer comments
  to English.
- [x] Keep Czech user-facing UI text, labels, validation messages, and domain
  copy unchanged.
- [x] Prefer singular English entity names, for example `User`, `Role`, `Song`,
  `Concert`, `SongFile`, `UserRole`, and `ConcertSong`.
- [x] Preserve old routes through aliases or redirects when presenter names
  change.
- [x] Keep existing Czech table names, columns, and stored values initially via
  Doctrine mapping. Do not introduce a risky production database rename merely
  for code naming.

Done when the application source uses a consistent English technical vocabulary
while users still see Czech UI text and existing links continue to work.

Current status: the canonical vocabulary and compatibility rules are defined,
and the application source now uses English technical identifiers without
changing the existing Czech database tables, columns, stored values, or UI
copy. Profile settings
now use English form keys, callbacks, component names, local variables, and
template variables while preserving Czech labels and the existing route. The
song presenter and template directory now use `Songs`; the original
`/skladby/...` URLs remain accepted through a tested one-way route while new
links use the canonical `/songs/...` path. The same compatibility pattern now
maps the original `/koncerty/...` URLs to `ConcertsPresenter`, with new links
using `/concerts/...`. Profile settings now use `SettingsPresenter` and
`/settings/...`, while the previous `/setting/...` route remains accepted.
Authentication now uses `AuthenticationPresenter`,
`AuthenticationFormFactory`, and `/authentication/login|logout`; the working
legacy `/sign/in` and `/sign/out` endpoints remain mapped explicitly. The
obsolete signup template, which referenced a nonexistent form component, was
removed. The authentication service now uses the canonical
`UserAuthenticator` name; its password and identity behaviour remains covered
by unit tests.

## 5. Complete Doctrine mapping and repositories

- [x] Validate all entities against the real schema.
- [x] Add the required relationships and collections for users/roles,
  concerts/songs, and songs/files.
- [x] Define constructors, default values, nullability, cascade behaviour, and
  orphan removal deliberately.
- [x] Add repository methods for authentication, roles, form choices,
  pagination, list views, and details.
- [x] Use English PHP property names while mapping them to existing database
  identifiers where required.

Done when entities and repositories fully express the application persistence
model without requiring Nette Database Explorer.

Current status: owning relations now expose inverse Doctrine collections for
users and roles, concerts and songs, and songs and files. The collections are
initialized in constructors and deliberately use neither ORM cascade
operations nor orphan removal. Required foreign keys, database deletion rules,
default columns, indexes, character sets, and collations now match the reviewed
local MariaDB 10.11 schema after the approved removal of the obsolete
`users.deprecated_role` column. The migration passed a local up/down/up check,
and Doctrine validation reports no difference in the final state.
All mapped PHP properties use English names while attributes preserve existing
database identifiers. Custom repositories cover authentication lookup, role
IDs/codes, form choices, pagination, counts, and inherited detail lookup. The
authorized production structure comparison confirmed every entity mapping and
exposed one legacy `BIT(1)` song flag. Migration `202609071500` converts it to
Doctrine's `TINYINT(1)` boolean representation. Live synchronization still
requires the separately authorized production migrations and post-deployment
validation.

## 6. Replace Nette Database Explorer with Doctrine ORM

- [x] Convert `UserAuthenticator` to use `UserRepository` and existing password
  verification.
- [x] Convert `UserService` role queries to Doctrine QueryBuilder or
  repositories.
- [x] Convert song CRUD and file-related persistence.
- [x] Convert concert CRUD and concert-song assignment updates in transactions.
- [x] Convert user listing, CRUD, and role synchronization.
- [x] Remove Explorer from presenters, services, and dependency injection.
- [x] Remove `database:` configuration and the direct `nette/database`
  dependency when no application references remain.
- [ ] Remove the obsolete `database:` block from the protected production
  `config/local.neon` before deploying the version without `nette/database`.
- [ ] Verify that production usernames, password hashes, roles, and sessions
  behave unchanged.

Done when searches for `Nette\\Database`, `->table(`, `->fetchAll(`, and
`->fetchPairs(` return no application usage.

Current status: authentication and role lookups now use Doctrine repositories,
while preserving the existing password verification, identity ID, display name,
and role codes. Song CRUD and song-file metadata persistence now use Doctrine as
well, with English repository methods, form keys, and template variables.
Concert CRUD and concert-song synchronization now run through Doctrine inside
database transactions. User CRUD and role synchronization now do the same.
Explorer has been removed from all application code and from the tracked local
and test configuration examples. The ignored local development configuration
was cleaned, and the direct `nette/database` package was removed without any
other dependency update. Existing protected production configuration is not
versioned or deployed; its obsolete `database:` block must be removed manually
and verified before deploying this version. `docs/deployment.md` records that
pre-deployment compatibility step.

## 7. Application security hardening

- [x] Add CSRF protection to all state-changing forms.
- [x] Require authorized POST requests for destructive actions.
- [x] Validate upload categories server-side, validate MIME type and size, use
  safe generated filenames, and prevent path traversal.
- [x] Use transactions for multi-entity writes and database/file coordination.
- [x] Ensure production debug mode is disabled.
- [x] Keep Phinx operations CLI-only. Do not reintroduce a public migration
  endpoint without an explicitly approved HTTPS, token, command-allowlist,
  concurrency-lock, and audit-log design.

Done when common authentication, authorization, CSRF, upload, and error-output
risks have automated coverage or explicit mitigations.

Current status: all state-changing forms use CSRF protection, destructive
actions require authorized POST forms, upload storage validates content and
coordinates filesystem/database changes, debug mode fails closed, and schema
migrations remain CLI-only. The security test suite covers these application
boundaries without using production data.

## 8. Reproducible build and code-quality tooling

- [x] Stop ignoring `composer.lock`, create an audited lock file, and commit it.
- [x] Run `composer validate` and `composer audit`.
- [x] Correctly split production and development dependencies.
- [x] Remove unused dependencies and obsolete scripts.
- [x] Add `.editorconfig` and align PHPCS/PHPStan configuration with the
  actual project layout.
- [x] Remove unnecessary vendored assets, source maps, and duplicate library
  variants while retaining required third-party notices.
- [x] Rewrite README installation, configuration, migration, testing, and
  architecture instructions for a clean clone.

Done when a clean checkout installs repeatably and quality commands have
documented, reliable results.

Current status: `composer.lock` is versioned, `composer validate --strict` and
`composer audit --locked --abandoned=report` passes without security
advisories, and the test suite runs with the locked
dependencies. Unused Nette Tester, Symfony Thanks, and the directly pinned
Symfony YAML 2.0.7 were removed. PHPUnit and PHP_CodeSniffer were upgraded to
security-fixed versions. The remaining abandoned Doctrine/Nettrine packages
are transitive compatibility dependencies and need to be handled with the
future framework upgrade rather than removed independently.
Editor defaults now enforce UTF-8, LF, final newlines, and the indentation
styles already used by PHP, Latte, NEON, and YAML files. PHPCS checks
first-party application, test, and web-entry PHP with the same PSR-1 baseline
used by CI. PHPStan level 0 covers application code, tests, CLI code,
migrations, and the web entry point; raising the level remains a future
code-quality improvement.
Public frontend libraries now retain only the minified files used by the
layout, required icon fonts and jQuery UI images, and upstream license notices.
`THIRD_PARTY_NOTICES.md` records component versions, copyright holders, and
licenses. Unused source maps, alternate builds, demos, metadata, and standalone
Bootstrap Icon SVG files were removed.
The bilingual English/Czech README now documents worldwide reuse and the
current Czech-only UI, requirements, locked dependency installation, ignored
local configuration, the fresh-database baseline, CLI-only migrations, runtime
directories, public-content boundaries, quality commands, architecture,
deployment safety, and the confirmed MIT License.

## 9. GitHub Actions continuous integration

- [x] Add a workflow for Composer validation and installation.
- [x] Run PHP syntax checks, PHPCS, PHPStan, Latte lint, and NEON lint.
- [x] Run the test suite against the test database.
- [x] Run the unit test suite on PHP 8.1 and PHP 8.3.
- [x] Run a locked dependency audit.
- [x] Run Doctrine mapping/schema validation against an isolated database once
  a complete schema baseline exists.
- [x] Add current-tree and history secret scanning.
- [x] Build a deploy artifact from the exact tested commit.
- [x] Protect the default branch and require the relevant checks.

Done when every pull request is checked without access to production secrets.

Current status: the workflow uses read-only repository permissions and
commit-pinned actions. Both supported PHP versions validate and install the
lock file, audit dependencies, check PHP syntax, run unit tests, and execute
the versioned quality tools. A separate MariaDB 10.11 job builds `smps_test`
from the complete migration chain, validates the Doctrine mapping and schema,
and runs the test suite with database integration tests explicitly enabled.
The artifact job waits for the secret, quality, and database jobs, checks out
the same commit SHA, installs production dependencies, excludes protected
runtime paths, verifies the archive, and uploads it with the SHA in its name.
A checksum-verified Gitleaks 8.30.1 binary scans the full fetched history with
redacted output before an artifact can be built. A local scan of the
then-current full history found no leaks. The scan was repeated successfully
at `de55ba6` over the tracked tree and all locally reachable history. On
2026-09-11 it passed again over the selected release tree, the one-commit
public history, and all locally reachable private history.

The private GitHub release candidate ran every job successfully on 2026-09-11.
GitHub Free rejected branch protection while the repository was private. After
publication, `main` was immediately protected with all five CI jobs required,
strict status checks, linear history, administrator enforcement, conversation
resolution, and force-push and deletion disabled.

## 10. Safe GitHub production deployment

- [x] Verify GitHub environment availability and define a sequence that never
  exposes production credentials without the required approval boundary.
- [ ] Create a protected GitHub `production` environment with manual approval
  and environment-scoped deploy credentials.
- [ ] Use a dedicated, least-privilege FTPS or SFTP account restricted to this
  application directory.
- [x] Assemble an explicit deploy artifact containing only application code,
  selected public configuration, dependencies, migrations, and approved web
  assets.
- [x] Exclude `config/local.neon`, `config/phinx.php`, legacy
  `config/phinx.yaml`, `www/dokumenty`, `log`, `temp`,
  `www/images/carousel`, and all production-only files both during artifact
  assembly and SFTP transfer.
- [x] Do not use `lftp mirror --delete`.
- [x] Start with a no-delete deploy.
- [ ] Introduce a reviewed deployment manifest that can remove only files
  installed by previous deployments.
- [x] Serialize deployments with a GitHub Actions concurrency group.
- [x] Keep database migrations separate from file deployment until there is a
  safe, explicitly approved execution mechanism.
- [ ] Retire GitLab deployment only after GitHub deployment succeeds on staging
  and production.

Done when a deployment can update application code without touching protected
production data and can be rolled back to a previous tested artifact.

Current status: `.github/workflows/deploy-production.yml` accepts only a
successful completed `CI` push run from the default branch, downloads its
SHA-named artifact, verifies its paths again, and uploads an explicit allowlist
over SFTP without remote deletion or migration execution. The workflow is
serialized and references the `production` environment. The environment,
reviewers, branch policy, environment-scoped credentials, restricted SFTP
account, and staging/production verification remain external setup steps.
GitHub's current plan rules do not provide required environment reviewers to
private repositories on Free, Pro, or Team. The private repository will
therefore run CI without production credentials; after the publication gates
pass, it will be made public, the protected environment and its credentials
will be configured, and only then may the manual deployment run. Repository-
wide production secrets are explicitly forbidden; see `docs/deployment.md`.

## 11. Public release gate

- [x] Create the empty target GitHub repository as private, without copying any
  GitLab commit, branch, tag, or tracked file.
- [x] Create and verify a confidential full-history Git bundle outside the
  repository and keep its location and checksum out of public release files.
- [x] Confirm current-tree and history secret scans are clean.
- [x] Use a clean public Git history or rewrite history after a verified backup.
- [x] Confirm legal permission for other published personal content; choir
  photographs are excluded from the public repository.
- [x] Confirm the MIT License and repository documentation are complete.
- [x] Verify clean installation from an empty checkout and fresh database.
- [x] Verify private GitHub CI and the exact release artifact.
- [x] Set the GitHub repository visibility to public after every other
  publication item is complete, then immediately protect `main` before
  accepting any further change.
- [ ] Configure the protected public-repository `production` environment, then
  verify staging deployment, production deployment, login, roles, uploads, and
  rollback.

Current status: on 2026-08-25 an isolated archive of the current release
candidate was created without `vendor`, local configuration, or ignored runtime
data. A clean `composer install` installed all 88 locked packages. The audit
found no security advisories and continued to report the three documented
abandoned transitive Doctrine/Nettrine compatibility packages. A newly created
`smps_test` database accepted all four migrations then present, Doctrine
reported valid and synchronized mapping, and all 74 tests passed with 366
assertions. PHP syntax,
PHPCS, PHPStan, Latte, and NEON checks also passed. The exercise found that the
documented CLI entry point had been accidentally excluded by `bin/.gitignore`;
`bin/console` is now explicitly versioned. Clean installation is therefore
verified. The production-schema comparison and hosted GitHub CI are now also
complete, while staging and production verification remain separate open
gates. The repository is made public before production credentials are
configured because GitHub Free, Pro, and Team cannot require environment
reviewers on private repositories. The manual deployment workflow must remain
unused until the public repository's protected `production` environment has
been verified.

On 2026-09-07, after the production structure comparison added the fifth
migration, the exact disposable `smps_test` database passed a complete
rollback, fresh migration, Doctrine schema validation, and all 74 tests with
367 assertions. The database test now verifies the final native
`skladby.active` type directly.

The `vrapa/smps` target was created empty and private on 2026-08-27. A reviewed
one-root-commit `main` snapshot was pushed privately on 2026-09-11. No GitLab
branch, tag, remote, or historical object was copied. Its GitHub CI completed
all five jobs successfully. The SHA-named deploy artifact was downloaded and
independently checked: all 7,378 archive entries were inside the allowlist,
with no unsafe path, local configuration, uploads, runtime data, or choir
photographs.

The selected history strategy is a new one-commit public snapshot; see
`docs/public-history.md`. It preserves the complete GitLab history in a
restricted verified bundle instead of rewriting or force-pushing the private
repository. Updated retained full-history bundles were created outside the
repository through the tested release source; their locations and SHA-256
records remain private. The snapshot uses the approved public commit identity,
has a Git tree identical to its selected release source, and contains exactly
one root commit. Checksum-verified Gitleaks scans of its tree, its public
history, and all locally reachable private history found no leaks. GitHub CI
independently repeated the public history scan successfully.

The verified root snapshot was published at `vrapa/smps` on 2026-09-11. Branch
protection was enabled immediately after the visibility change and before any
further repository commit. No production environment, deployment credential,
migration, or deployment was created or run as part of publication.

## Working agreement

Post-publication Czech, English, German, and Dutch UI localization is tracked
in `docs/localization-plan.md`. It is a separate implementation stream and does
not authorize a production database migration or deployment.

- Each completed section is committed and pushed separately.
- Production-impacting actions require explicit approval.
- Keep unrelated changes out of each commit.
- Update this checklist whenever an implementation decision changes.
