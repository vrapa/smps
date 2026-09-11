# Database and test baseline inventory

This inventory records what can be proven from the repository and the local
Docker database without reading production credentials or connecting to
production. It is the starting point for the test baseline and the later
Doctrine-only migration.

## Isolated test database

Use a disposable MySQL or MariaDB database whose name ends in `_test`.

1. Copy `config/test.example.neon` to the ignored `config/test.neon`.
2. Copy `config/phinx.example.php` to the ignored `config/phinx.php`.
3. Replace the empty example credentials with a dedicated test-only account
   restricted to the test database.
4. Use the Phinx `testing` environment explicitly. Do not run migrations,
   rollbacks, schema generation, or fixture cleanup without checking the
   selected database name first.

The committed examples intentionally contain no production host, username,
password, data, or password hash. A future automated test bootstrap must fail
closed unless the selected database name ends in `_test`.

## Repository schema inventory

Doctrine currently maps seven tables:

| Table | Entity | Present in tracked migrations |
| --- | --- | --- |
| `users` | `User` | No; assumed to predate Phinx |
| `roles` | `Role` | Yes |
| `roles2users` | `UserRole` | Yes |
| `skladby` | `Song` | No |
| `koncerty` | `Concert` | No |
| `skladby2koncerty` | `ConcertSong` | No |
| `soubory2skladby` | `SongFile` | No |

Migration `202309261550` now creates the complete seven-table schema and seeds
the three application roles. Its version was already recorded in existing
installations, so Phinx does not replay it there; an empty database receives
the complete current schema. The two following historical role migrations are
guarded: they still migrate an old installation containing `users.role`, but
become no-ops when the initial schema has already created the final structure.
Migration `202608201200` removes the transitional `deprecated_role` column
from upgraded installations. Migration `202609071500` normalizes the legacy
`skladby.active` `BIT(1)` column to the `TINYINT(1)` representation used by
Doctrine DBAL for a boolean.

## Relationships visible in mappings or migrations

- `roles2users.roles_id` references `roles.id` with restricted deletion.
- `roles2users.users_id` references `users.id` with cascading deletion.
- `koncerty.createdBy` and `skladby.createdBy` reference `users.id`.
- `skladby2koncerty` joins concerts and songs.
- `soubory2skladby.skladby_id` references songs and its `createdBy` references
  users.

Doctrine join mappings now state the locally observed nullability and deletion
rules explicitly. The owning relations have inverse collections for users and
roles, concerts and songs, and songs and files. They deliberately have no ORM
cascade operations or orphan removal.

## Local schema candidate reviewed on 2026-08-20

The running local `SMPS-db` container uses MariaDB 10.11.18 and contains the
seven expected application tables plus `_phinxlog`. A temporary schema-only
dump was created inside the container with rows, triggers, routines, events,
and `DEFINER` clauses excluded. Automated review found no data statements. The
raw dump, including environment-specific auto-increment counters, was not
copied into the repository.

Comparison with this local schema identified and corrected the following ORM
metadata differences:

- initially mapped the required `users.deprecated_role` legacy column so the
  comparison could not remove it implicitly; after confirming that role data
  had already moved to `roles2users`, a dedicated migration now removes it;
- marked every existing foreign key as non-nullable;
- recorded `ON DELETE CASCADE` for `roles2users.users_id` and
  `soubory2skladby.skladby_id` while retaining restricted deletion elsewhere;
- recorded the `users.notifikace` default, the existing `koncerty.createdBy`
  index, and Doctrine text length 65,535 for `koncerty.poznamka`;
- recorded the existing per-table character sets and collations, including
  the Czech collation used by the first nine textual `users` columns.

After these corrections, both `orm:validate-schema` and
`orm:schema-tool:update --dump-sql` report that the local database and entity
metadata are fully synchronized. Before removing `deprecated_role`, an
aggregate check confirmed that no local user lacked a `roles2users` record.
Migration `202608201200` was then verified locally in an up/down/up sequence;
the rollback restores only an empty compatibility column, not obsolete role
values, and the final local state has the column removed. This is strong
implementation evidence that was subsequently checked against the authorized
production structure export described below.

## Production structure reviewed on 2026-09-07

An authorized structure-only production export was reviewed outside the
repository. It contains the seven expected application tables plus
`_phinxlog`, and automated checks found no `INSERT`, `REPLACE`, `LOAD DATA`,
trigger, routine, event, or `DEFINER` statement. The raw export remains outside
version control.

The comparison covered column types and lengths, defaults, nullability,
character sets and collations, primary and unique keys, indexes, foreign keys,
deletion rules, and auto-increment behaviour. All mapped structures agree
except for two known upgrade-state differences:

- Production still contains `users.deprecated_role`. The already prepared
  migration `202608201200` removes it after the role-assignment preflight is
  completed during a separately authorized deployment.
- Production stores `skladby.active` as `BIT(1)`, while Doctrine DBAL maps the
  entity boolean and the fresh-install baseline to `TINYINT(1)`. Migration
  `202609071500` normalizes the production column without changing its `0`/`1`
  values and provides a `BIT(1)` rollback.

The export intentionally contains no `_phinxlog` rows, so it does not prove
which migration versions production has recorded. Migration status must be
checked read-only before any production migration is authorized. No statement
from the export was executed against any database.

## Fresh-install verification on 2026-08-21 and 2026-09-07

The complete migration chain was run against a newly created disposable local
MariaDB database named `smps_test`. It created all seven mapped tables, seeded
exactly three roles, left neither `users.role` nor `users.deprecated_role`, and
recorded all four migration versions then present. A complete rollback removed
every application table, after which a second migration run recreated the
schema. Doctrine then reported both mapping validity and schema
synchronization.
After adding the production-derived boolean normalization, the exact
`smps_test` target passed another complete rollback and fresh migration of all
five versions on 2026-09-07. Doctrine validation passed, and the database test
suite directly confirmed that `skladby.active` ends as `tinyint(1)`.

CI repeats the same guarantees in a dedicated MariaDB 10.11 service. Before
running Phinx, the workflow rejects a configured database name that does not
end in `_test`. The integration test verifies all expected tables, absence of
both legacy role columns, the three seeded role codes, valid Doctrine mapping,
and schema synchronization. The deployment artifact is built only after this
database job succeeds.

Database-backed application tests begin a transaction only after verifying the
connected database name ends in `_test`, and roll it back after each test. The
current user/authentication scenario leaves both `users` and `roles2users`
empty while exercising the real repositories, role synchronization, password
verification, pagination, and identity construction.

The song/concert scenario likewise rolls back its creator, songs, concerts,
concert-song rows, and song-file metadata. It exercises repository counts,
choices and pagination, repertoire replacement, and concert deletion without
writing a runtime upload file. After the test, every affected application
table remains empty.

Presenter integration uses the real DI container, security user, presenter
factory, Doctrine repositories, and templates. Authenticated GET requests load
song, concert, and user edit forms with persisted defaults and seeded choices;
the authentication form is also checked for its CSRF token. These requests run
inside the same `_test`-guarded transaction and leave no database rows behind.

The application selects the ignored `config/test.neon` only when
`SMPS_ENV=test` exactly; other values continue to use `config/local.neon`.
Phinx operations must likewise name the `testing` environment explicitly.
These checks used only `smps_test` and did not modify the local development or
production database.

## Evidence still required

Before a production migration, check the read-only Phinx status and confirm
that every production user has at least one `roles2users` assignment. After the
two pending schema migrations are applied through the CLI under a separately
authorized, backup-gated deployment, validate the live schema and application
behaviour. Do not copy usernames, emails, password hashes, role assignments,
or other row data into the repository.

Do not delete or rewrite production Phinx history. The baseline deliberately
reuses the already recorded `202309261550` version so existing installations
skip it while fresh installations receive the complete schema. Any write to
the production migration table still requires a separate backup and explicit
authorization.
