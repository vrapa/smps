# GitHub production deployment

Implementation status (2026-09-22): the workflow below is a prepared SFTP
transfer, not yet a verified Webglobe deployment. The protected GitHub
`production` environment exists, port 222 and the authenticated target path are
configured as environment variables, and an external read-only probe verified
password-authenticated SFTP access. Environment secrets, trusted host identity,
key authentication, release activation, cache handling, durable artifact
retention, exact rollback, isolated rehearsal, and production acceptance remain
open and are tracked in
[completion-plan.md](completion-plan.md), milestones 1 and 6–9.

The owner confirmed that production already runs on Webglobe at
[https://smps.rkcomputer.cz](https://smps.rkcomputer.cz). Future deployment updates
this existing installation. A planned outage is acceptable: the target procedure
is maintenance mode, a consistent backup, in-place update of application and
dependencies, any separately approved configuration/schema changes, cache refresh,
functional checks, and reopening the site. Preserve all existing runtime data.
Atomic switching and a permanent staging site are not required. The current
transfer-only workflow still needs maintenance and recovery handling implemented.

The production workflow deploys only an artifact created by a successful `CI`
push run on the repository's default branch. It is started manually with the
numeric CI run ID, then waits for the protection rules configured on the
GitHub `production` environment. The production workflow itself must also run
from the default branch; artifact validation uses the policy from that trusted
workflow revision, including when an older tested artifact is selected for
rollback.

The hosting document root must point to the deployed application's `www`
directory. The artifact also contains a project-root `.htaccess` guard for the
legacy layout: it rejects direct access to existing project-root files and
directories and internally routes public requests into `www`. This is
defence-in-depth, not a substitute for the correct document root. The archive
policy requires the guard and the deployment uploads it before application
directories.

CI audits every path in the current source and reachable public Git history.
The artifact is then checked at build time and again before deployment. The
policy rejects protected runtime paths, unapproved first-party photographs and
music/document/database formats, secret-bearing file names, traversal,
duplicate paths, links, special entries, oversized content, and unexpected
top-level paths. Media shipped inside Composer dependencies is permitted; the
small reviewed first-party icon allowlist is explicit in
`tools/audit_public_content.py`.

Do not invoke this workflow while the GitHub staging repository is private.
Required environment reviewers are available only to public repositories on
GitHub Free, Pro, and Team plans, and environment secrets are unavailable to
private repositories on GitHub Free. After the reviewed snapshot and private
CI pass the publication gates, make the repository public, configure and
verify the protected environment, and only then add deployment credentials or
start this workflow. Never substitute repository-wide production secrets.

The deployment uses SFTP with strict host-key verification. It overwrites or
adds only the allowlisted application paths contained in the artifact. It does
not delete remote files and does not run database migrations.

Choir photographs in `www/images/carousel` are production-managed content,
not repository assets. CI excludes and rejects this directory in deploy
artifacts, and SFTP deployment never deletes its remote contents. Provision
the photographs separately only after publication rights have been verified.

## One-time production configuration cleanup

The application no longer installs or uses Nette Database Explorer. Before the
first deployment containing this change:

1. Back up the protected production `config/local.neon` outside the deployed
   application paths.
2. Remove only its obsolete top-level `database:` block. Keep the Doctrine
   connection parameters and all unrelated production settings unchanged.
3. Validate the adjusted configuration in an isolated test environment, then
   check it on the hosting during maintenance before reopening the application.
4. Deploy the tested artifact and verify login, roles, users, songs, concerts,
   and uploads.

The deployment artifact deliberately excludes `config/local.neon`, so the
workflow cannot perform this cleanup and must never replace that protected
file.

## Installation language

The user-interface language is selected once for the whole installation by
the `parameters.locale` value. Supported values are `cs`, `en`, `de`, and
`nl`; versioned configuration defaults to `cs`. To select another language,
set the value in the protected `config/local.neon`, validate the application in
an isolated test environment, and clear the Nette cache through the normal
deployment procedure.

Keep production on `cs` until the four-locale acceptance matrix and
fluent-speaker review in `docs/localization-plan.md` are complete. All four
catalogues are implemented, but production language changes still require
hosting acceptance for the selected locale.

The deployment artifact excludes `config/local.neon` and therefore cannot
change the production language by itself. A production locale change is a
reviewed protected-configuration operation, not a database migration.

The public name shown in the navigation and browser title is configured by
`parameters.applicationName`. It defaults to `SMPS Bruntál`; another choir can
override it in `config/local.neon` without changing a template. The value is
public display text, not a place for credentials or other private data.

Concert input and localized display use the IANA timezone in
`parameters.timezone`, defaulting to `Europe/Prague`. Set it deliberately for
another installation and verify PHP Intl before deploying this release. CI
installs and tests Intl, but the Composer platform requirement must not be added
until the development, CI, and Webglobe runtimes have all been checked.

## GitHub environment setup

The `production` environment was created on 2026-09-22 and is restricted to
protected branches. Because the repository currently has one eligible
administrator, it deliberately has no required reviewer: manual workflow
dispatch is the owner's approval action. If a second trusted maintainer is added,
require that reviewer and prevent self-review. No environment secrets are stored
until the secure account, target path, and trusted host key have been verified.

Environment rules are:

1. Restrict deployments to the protected default branch.
2. Keep production deployment manual and serialized.
3. Store the following values on the environment, not as repository-wide
   credentials.

Environment secrets:

- `SFTP_HOST`: production SFTP hostname.
- `SFTP_USERNAME`: dedicated deployment account.
- `SFTP_PRIVATE_KEY`: private key for that account.
- `SFTP_KNOWN_HOSTS`: pinned `known_hosts` line obtained from the hosting
  provider through a trusted channel. The workflow deliberately does not use
  `ssh-keyscan` at deployment time.

Environment variables:

- `SFTP_PORT`: set to Webglobe's documented port `222` (configured 2026-09-22).
- `SFTP_REMOTE_PATH`: application root as seen by the restricted SFTP account
  (verified and configured 2026-09-22; its value remains in protected operational
  configuration rather than public documentation).

The account must be restricted to this application and must not provide access
to database data, user uploads outside the application root, or unrelated
hosting content. Test transfer and account restrictions using an isolated target
where practical; verify the exact production paths before the maintenance update.

The existing hosting account accepts password-authenticated SFTP on port 222 and
can enter the configured application target. It authenticates to SSH but the
server disables command execution for that account. The transfer workflow can
therefore use this endpoint only after key authentication and trusted host-key
provenance are established. Maintenance mode, cache refresh, release cleanup,
and rollback commands need either a separately enabled command-capable account
or an explicit manual WebSSH/control-panel procedure. The verification probes did
not modify remote files.

## Deploying and rolling back

Open a successful `CI` run created by a push to the default branch and copy its
numeric run ID from the URL. Start `Deploy production`, enter that ID, review
the pending environment deployment, and approve it only after checking the
commit SHA and artifact.

The target rollback procedure keeps maintenance enabled and restores the last
working application/dependencies from a retained artifact or the pre-update
hosting backup, handles files added by the failed release, refreshes cache, and
checks the site before reopening. The existing upload-only workflow does not yet
implement this full procedure. Database migrations remain a separate, explicitly
approved CLI operation; check schema compatibility before deployment or rollback.

Rehearse the update and recovery in an isolated local/CI environment. Temporary
Webglobe staging is useful if readily available, but is optional. The first
hosting deployment may update production during the agreed outage, after backup
and rehearsal, with hosting-specific checks completed before reopening. Retire
GitLab deployment after successful production verification and recovery readiness.
