# Authorized schema-only export

This runbook is for obtaining the evidence required to compare the production
schema with Doctrine mappings and create a reviewed fresh-install baseline. It
does not authorize a production connection. Obtain approval from the database
owner before running any command below.

## Safety requirements

- Use a temporary read-only account restricted to the SMPS database. It must
  not have write, migration, replication-administration, or global privileges.
- Run the export from a trusted machine over certificate-verified TLS.
- Use an interactive password prompt (`--password` with no value). Never put a
  password in shell history, a process argument, this repository, or a ticket.
- Write the raw export to a restricted directory outside the repository.
- Choose a new output filename and verify the command exit status. The dump
  client may create or overwrite its result file before a later error, so a
  file's presence alone does not prove a complete export.
- Export no rows, triggers, routines, or events. These objects may contain
  personal data, application secrets, or environment-specific `DEFINER`
  accounts.
- Do not import the export into production and do not alter the production
  Phinx migration table.

Record the server product and version before choosing the matching command.
Replace every value in angle brackets locally; do not paste real hostnames,
usernames, certificate paths, or database names into this document.

## MySQL client

```powershell
mysqldump `
  --host=<approved-host> `
  --port=<approved-port> `
  --user=<read-only-user> `
  --password `
  --ssl-mode=VERIFY_IDENTITY `
  --ssl-ca=<trusted-ca-file> `
  --no-data `
  --skip-triggers `
  --skip-routines `
  --skip-events `
  --single-transaction `
  --skip-lock-tables `
  --no-tablespaces `
  --set-gtid-purged=OFF `
  --result-file=<restricted-path-outside-repository> `
  <approved-database>
```

## MariaDB client

```powershell
mariadb-dump `
  --host=<approved-host> `
  --port=<approved-port> `
  --user=<read-only-user> `
  --password `
  --ssl-verify-server-cert `
  --ssl-ca=<trusted-ca-file> `
  --no-data `
  --skip-triggers `
  --skip-routines `
  --skip-events `
  --single-transaction `
  --skip-lock-tables `
  --result-file=<restricted-path-outside-repository> `
  <approved-database>
```

`--no-data` excludes table rows. Triggers are otherwise enabled by default in
both clients, so `--skip-triggers` is intentional. Routines and events are also
excluded explicitly rather than relying on client-version defaults.

## Review before repository use

Keep the raw export outside the repository and review it locally. At minimum:

1. Confirm it contains no `INSERT`, `REPLACE`, `LOAD DATA`, trigger, routine,
   event, or `DEFINER` statements.
2. Confirm it contains definitions only for the seven expected application
   tables listed in `docs/database-inventory.md`.
3. Review comments, generated expressions, default values, database names,
   storage paths, and auto-increment counters for environment-specific or
   identifying information.
4. Compare types, lengths, signedness, defaults, nullability, character sets,
   collations, indexes, unique constraints, foreign keys, deletion rules, and
   auto-increment behaviour with every Doctrine mapping.
5. Create a separate sanitized baseline candidate. Review its diff before it is
   ever added to Git; do not commit the raw export.

The baseline must be designed so a fresh database can be created without
replaying a new initial migration against production. Recording or changing a
production migration version remains a separate, backup-gated operation that
requires explicit authorization.

## Client references

- [MySQL `mysqldump` options](https://dev.mysql.com/doc/refman/8.0/en/mysqldump.html)
- [MySQL stored-program export controls](https://dev.mysql.com/doc/refman/8.4/en/mysqldump-stored-programs.html)
- [MariaDB `mariadb-dump` options](https://mariadb.com/docs/server/clients-and-utilities/backup-restore-and-import-clients/mariadb-dump)
