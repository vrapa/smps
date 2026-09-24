# GitHub production deployment

Implementation status (2026-09-23): GitHub CI remains the trusted artifact
builder, but direct GitHub-hosted runner transfer is not the selected production
path. Two runner probes timed out before SFTP authentication while the same port
was reachable from the development workstation. The local
`tools/deploy-production.ps1` handoff is implemented but must be verified from
merged `main` before its first approved production use. The protected GitHub
`production` environment exists, port 222 is configured as an environment
variable, and an external read-only probe verified the dedicated account's
password-authenticated SFTP boundary and target `.`. The protected environment
value was updated to that target and read back successfully. A read-only
control-panel inspection also
verified a compatible web runtime, daily provider backups, an editable `www`
document-root mapping, and the ability to create a directory-scoped transfer
account. Public-key authentication was tested and rejected by the provider's
SFTP endpoint, so the prepared workflow now uses a protected password without
placing it on the command line. The environment-scoped connection secrets and
independently corroborated host key remain configured only for the transition
and must be removed after the first accepted local deployment. Local
prepare and password-authenticated read-only SFTP preflight are complete for
`main` run `35838496796`. The bounded write/create/rename/delete rehearsal passed
from merged `main` run `35848005204` and removed its synthetic test data. The
local synthetic overlay and exact snapshot rollback then passed from merged
`main` run `35849397823` for revision
`42ab07fa20416be989b1c6ea9536cb4e4ea78c88`. The manifest-bound artifact and
repeat overlay/rollback rehearsal passed for merged `main` run `35858438441`,
revision `3a88ab655310d72af99043eb9ece35f6e685cd61`. Release activation, durable artifact
retention, hosting-specific recovery, presenter-flow rehearsal, and production
acceptance remain open and are tracked in
[completion-plan.md](completion-plan.md), milestones 1 and 6–9.

The reversible cache operation was added in PR #48 and its StrictMode empty-list
regression was caught by the first post-merge rehearsal, fixed in PR #49, and
retested from merged `main`. Run `35901054288` produced revision
`616e3510232038619a1026ea8197b34d1d7c9602` with artifact SHA-256
`f4c13986db927dc01ebc2ea54636549114714113ac2a706e867f571814644a59`.
The full local rehearsal then passed candidate overlay, protected-path
preservation, no-delete stale-file behavior, reversible cache rotation, and exact
snapshot rollback using only synthetic data.

The owner confirmed that production already runs on Webglobe at
[https://smps.rkcomputer.cz](https://smps.rkcomputer.cz). Future deployment updates
this existing installation. A planned outage is acceptable: the target procedure
is maintenance mode, a consistent backup, in-place update of application and
dependencies, any separately approved configuration/schema changes, cache refresh,
functional checks, and reopening the site. Preserve all existing runtime data.
Atomic switching and a permanent staging site are not required. Candidate-bound
maintenance and reversible Nette cache rotation are implemented; their first live
use, OPcache handling, stale-file reconciliation, and full release recovery remain
production-window work.

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

The Webglobe control panel currently maps the production subdomain to the
application root and offers an editable directory target. Change it to the
application's `www` directory only during the approved maintenance window, after
fresh backups. Immediately verify the public site, rewrites, and denial of direct
access to configuration, dependencies, logs, backups, and other non-public paths.

CI audits every path in the current source and reachable public Git history.
The artifact is then checked at build time and again before deployment. The
policy rejects protected runtime paths, unapproved first-party photographs and
music/document/database formats, secret-bearing file names, traversal,
duplicate paths, links, special entries, oversized content, and unexpected
top-level paths. Media shipped inside Composer dependencies is permitted; the
small reviewed first-party icon allowlist is explicit in
`tools/audit_public_content.py`.

Every artifact also contains `RELEASE_SHA` and `RELEASE_MANIFEST.sha256`.
`RELEASE_SHA` must equal the successful selected CI run revision. The manifest
lists every other regular artifact file exactly once with its SHA-256 digest;
the archive audit rejects missing, extra, duplicate, malformed, or mismatched
entries. Both metadata files are uploaded with the application so the installed
revision and exact candidate file set can be checked without exposing secrets.

## Read-only HTTP verification

Run the repeatable HTTP boundary check before maintenance to record a baseline,
and run the stricter acceptance mode before reopening the site:

```powershell
./tools/verify-production-http.ps1 -BaseUrl https://smps.rkcomputer.cz -Mode Baseline
./tools/verify-production-http.ps1 -BaseUrl https://smps.rkcomputer.cz -Mode Acceptance
```

The command performs HTTPS GET requests without following redirects or reading
response bodies. It requires the application entry point and a known public
asset to respond, rejects redirects away from the production origin, and
requires configuration, dependencies, runtime storage, Git metadata, uploads,
and release metadata to return only HTTP 403 or 404. Acceptance mode also checks
the anonymous sign-in page and rejects direct access through `/www/index.php`.
Maintenance mode requires the root to return HTTP 503 and the dependency-free
maintenance page to return HTTP 200; protected paths may return 403, 404, or the
same maintenance 503 and are checked again strictly after reopening.
The release SHA itself is verified through the authenticated SFTP metadata
read-back; it is deliberately not exposed through HTTP.

A read-only pre-deployment baseline and acceptance probe on 2026-09-23 passed:
`/` redirected within the same origin, the anonymous sign-in page and favicon
returned HTTP 200, and the tested protected paths plus `/www/index.php` returned
only HTTP 403 or 404. This does not replace the post-upload acceptance run or
prove that the hosting document root has been changed to `www`.

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

## Production migration configuration

The legacy production `config/phinx.yaml` is not a usable deployment interface.
Its default environment is obsolete, it depends on the optional YAML parser, and
its local-socket connection failed from WebSSH. No migration ran during that
read-only status attempt.

The artifact instead includes `config/phinx.production.example.php`. It reads
the existing protected Doctrine connection from `config/local.neon` at runtime,
so no database password is duplicated in a command, tracked file, or second
manually maintained configuration. During the approved maintenance window,
after backing up protected configuration and uploading the tested artifact:

```sh
cp config/phinx.production.example.php config/phinx.php
php8.1 vendor/bin/phinx status --environment production --configuration config/phinx.php
```

`config/phinx.php` is ignored and excluded from deployment artifacts. Verify that
status shows only the expected three 2023 migrations as applied and the two
reviewed transition migrations as pending. Run `migrate` only after separate
explicit authorization and only with `php8.1`, the `production` environment, and
this PHP configuration. Preserve the legacy YAML file in the private pre-update
backup; remove it from the live tree only after the PHP configuration and recovery
procedure have been accepted.

The production server reports MySQL 5.5.62. The locked Doctrine DBAL 3.9 branch
already deprecates MySQL 5.6 in favour of 5.7 or newer, so this still older server
requires an explicit candidate boot/schema check and representative read tests
during maintenance before any migration. The two pending SQL changes themselves
are simple column operations, but the local MariaDB 10.11 rehearsal is not proof
of full MySQL 5.5 runtime compatibility. A database-service upgrade is a separate
hosting/data-migration decision, not part of the application SFTP overlay.

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
until the development, CI, and Webglobe CLI runtimes have all been checked. The
Webglobe web runtime already has Intl enabled.

## Verified hosting runtime and recovery capabilities

The Webglobe web runtime was inspected read-only on 2026-09-22. It uses PHP
8.1.34 through FPM/FastCGI with `pdo_mysql`, `intl`, `mbstring`, `fileinfo`, and
Zend OPcache enabled. The observed 256 MB memory limit, 90-second execution
limit, and 256 MB upload/post limits are compatible with the current application.
The server default timezone differs from the application's explicit
`Europe/Prague` setting, so keep the application setting and verify date handling
during acceptance. A WebSSH check on 2026-09-23 confirmed the matching `php8.1`
CLI and required extensions, suitable ownership/modes for protected and runtime
paths, a 901 MB installation, and 281 GB free on its backing filesystem. The
Webglobe control panel subsequently confirmed more than 90 GB free within the
hosting account's own quota, comfortably above the temporary deployment and
recovery allowance. Private control-panel evidence and hosting identifiers are
not stored in the repository. The database reports MySQL 5.5.62 and requires the
compatibility gate described above.

The account exposes a temporary browser WebSSH console for one hour after
two-factor authentication. Permanent console access is a separate paid option.
Neither was activated during inspection. A temporary console may support the
maintenance-window checks and cache/configuration operations if rehearsed; it is
not proof that the SFTP deployment account can execute unattended commands.

Webglobe provides daily FTP snapshots and daily database backups, with controls
for preparing a downloadable archive and restoring a selected backup. No backup
or restore action was started during inspection. Before deployment, create or
request fresh backups, preserve an independent copy where practical, verify
download/restore access, and retain the last compatible application artifact and
manifest. Provider retention alone is not the rollback plan.

## Local artifact handoff

The supported transfer origin is a trusted Windows workstation with PowerShell
7.2 or newer, authenticated GitHub CLI, Python, `tar`, and Windows OpenSSH
`sftp`/`ssh-keygen`. GitHub CI still performs all tests and creates the production
dependency artifact. The workstation never rebuilds it.

Create the private local connection file once:

```powershell
Copy-Item config/deploy.example.psd1 config/deploy.local.psd1
```

Fill `Host`, `UserName`, `Port`, `RemotePath`, and `KnownHostsFile` in the ignored
copy. Keep `RemotePath = '.'` for the verified restricted account. The file must
not contain a password. `KnownHostsFile` must reference the local entry whose key
was independently corroborated; the script uses strict host-key checking and does
not learn a key from the network during deployment.

For local read-only diagnostics that must run without an interactive SFTP
prompt, store the password as a Windows DPAPI-protected credential:

```powershell
./tools/save-production-sftp-credential.ps1
# When the terminal prompt is not visible in Codex Desktop:
./tools/save-production-sftp-credential.ps1 -Gui
```

The command reads `UserName` from the ignored deployment configuration and
prompts for the password without echoing it. It writes only
`config/credentials/production-sftp.credential.xml`, which Git ignores and
which can be decrypted only by the same Windows account on the same
workstation. Do not paste the password into chat, command-line arguments, the
PowerShell data file, or repository documentation.

Select a successful `CI` run created by a push to protected `main`, then use its
numeric run ID in one of these modes:

```powershell
# Validate and audit the exact GitHub artifact; no production connection.
./tools/deploy-production.ps1 -CiRunId 123456789 -Mode Prepare

# Exercise a synthetic local overlay and exact rollback; no production connection.
./tools/deploy-production.ps1 -CiRunId 123456789 -Mode Rehearse

# Additionally verify password login, target, and chroot using only pwd/cd/pwd.
./tools/deploy-production.ps1 -CiRunId 123456789 -Mode Preflight

# After explicit approval, test create/rename/delete in one disposable directory.
./tools/deploy-production.ps1 -CiRunId 123456789 -Mode WriteTest

# Enable and read back a maintenance marker bound to the selected release.
./tools/deploy-production.ps1 -CiRunId 123456789 -Mode MaintenanceOn
./tools/verify-production-http.ps1 -BaseUrl https://smps.rkcomputer.cz -Mode Maintenance

# Require that marker, repeat the preflight and exact confirmation, then overlay.
./tools/deploy-production.ps1 -CiRunId 123456789 -Mode Deploy

# After reviewed configuration/schema work, preserve the old Nette cache and
# create a clean cache for the same tested release.
./tools/deploy-production.ps1 -CiRunId 123456789 -Mode CacheRotate

# Rollback only: while maintenance is still enabled, restore the preserved cache.
./tools/deploy-production.ps1 -CiRunId 123456789 -Mode CacheRestore

# Success path: after non-HTTP checks pass, remove only the verified marker and
# test immediately.
./tools/deploy-production.ps1 -CiRunId 123456789 -Mode MaintenanceOff
./tools/verify-production-http.ps1 -BaseUrl https://smps.rkcomputer.cz -Mode Acceptance
```

Every mode requires a clean local checkout of the current GitHub default-branch
head. It verifies the repository, successful completed `CI` push run, default
branch and commit SHA, downloads the existing `smps-<SHA>` artifact, audits the
archive, extracts it into a temporary directory, and rejects protected paths.
Use `-KeepWorkspace` only for local troubleshooting; the default scopes cleanup
strictly to its generated temporary directory.

The command holds an exclusive workstation-local deployment lock for its entire
run. A concurrent SMPS prepare, rehearsal, preflight, write test, maintenance
change, or deployment fails before artifact work begins; an abandoned process
releases the operating system file lock automatically.

Remote modes let OpenSSH request the password directly in the console; the script
does not receive, store, print, or pass it on the command line.
On Windows the fixed SFTP commands are streamed to a normal interactive session;
the script deliberately does not use `sftp -b`, because batch mode disables the
console password prompt. The exit status, error output, and restricted-root result
are still checked.
`Deploy` prompts twice because it completes and checks a separate read-only
connection before asking for the exact `DEPLOY <short-SHA>` confirmation and
opening the upload connection. It first requires the remote maintenance marker
to match the selected full SHA. The upload uses the reviewed allowlist and no
remote delete command. It does not create backups, change `config/local.neon`,
run migrations, clear caches, or perform acceptance checks. Those remain explicit
maintenance-window steps.
After the overlay, the same SFTP session downloads the remote `RELEASE_SHA` and
`RELEASE_MANIFEST.sha256` into the temporary workspace. Their revision and
manifest digest must match the verified local artifact or the deployment command
fails before reporting upload success.

`CacheRotate` is also bound to the selected artifact and first verifies the same
maintenance marker. After an exact confirmation it adds a release marker to the
existing `temp/cache`, atomically renames that directory to
`temp/cache.before-<short-SHA>`, creates a fresh mode-755 `temp/cache`, and reads
back markers from both directories. It does not recursively delete cached files.
If any step fails, keep maintenance enabled and inspect those two exact paths
before doing anything else. The local `Rehearse` mode exercises the equivalent
rotation and exact restoration using only synthetic files.

`CacheRestore` is the rollback counterpart. It verifies both release-bound cache
markers before asking for confirmation, preserves the candidate cache as
`temp/cache.failed-<short-SHA>`, restores the previous cache directory, and
removes only the marker that the rotation inserted there. The failed cache stays
available for inspection. After a successful production acceptance and the
agreed rollback-retention period, remove the SHA-named old cache manually through
WebSSH; never use a wildcard or a broad `temp` deletion.

This rotates the Nette filesystem cache only. `php8.1` in WebSSH is a separate CLI
SAPI, so calling `opcache_reset()` there would not prove that the web PHP-FPM
cache was reset. [Webglobe documents PHP settings](https://www.webglobe.cz/poradna/jak-zjistit-a-zmenit-verzi-php)
under **Hosting > Web > PHP settings** and notes that changes can take up to 20
minutes, but does not document a per-site OPcache restart in the reviewed public
guidance. Do not toggle the PHP version merely as an undocumented cache-reset
shortcut. Confirm the hosting-safe method in Webglobe Admin or with support;
until then, keep HTTP acceptance and log review as mandatory gates before
reopening.

`MaintenanceOn` atomically creates `.maintenance/`, uploads its release marker,
reads it back, and requires it to contain the exact selected release SHA. Creation
fails instead of overwriting an existing maintenance operation. The versioned
`www/.htaccess` returns HTTP 503 while that directory exists, both with the legacy
project-root mapping and with the required `www` document root; the static
response has no application or database dependency. The marker is outside the
artifact, so it survives the overlay. `MaintenanceOff` first downloads the
marker and refuses to remove it unless it belongs to the same selected release,
then requires the exact `MAINTENANCE OFF <short-SHA>` confirmation and removes
only the verified marker and its now-empty directory. Run HTTP
Maintenance mode before upload. After SFTP metadata, configuration, migration,
cache, and CLI checks pass, remove the marker and run HTTP Acceptance immediately.
If acceptance fails, enable the same marker again before recovery.

An isolated Apache test on 2026-09-23 exercised both supported mappings. With
`www` as document root, the marker produced HTTP 503 with the reviewed static
page. With the legacy application-root mapping, it produced the same page while
direct access to protected configuration remained HTTP 403. The marker directory
is ignored by Git and was removed after the local test. Production enablement is
still pending the approved maintenance window.

`WriteTest` also starts with the read-only preflight. After an exact confirmation
it creates one random `.smps-deploy-check-*` directory at the restricted account
root, uploads a synthetic marker, renames and removes the marker, then removes the
empty directory. It neither reads nor changes application files. Treat any failed
cleanup as a stop condition and remove only the printed test directory after
inspection; never broaden cleanup to a wildcard.

`Rehearse` never opens an SFTP connection. It builds a synthetic previous
installation inside the temporary deployment workspace, snapshots it, overlays
the selected tested artifact, verifies that ignored configuration, uploads,
carousel photographs, logs, and cache remain byte-identical, and confirms that
the no-delete method leaves a deliberately obsolete file in place. It then
replaces the synthetic tree from the snapshot and requires an exact file/hash
manifest match. It additionally rotates the synthetic Nette cache, writes a
candidate-only cache entry, restores the previous cache byte-for-byte, and removes
only the synthetic failed cache inside the bounded temporary workspace. This
proves the local update, cache and snapshot-rollback mechanics; hosting backup
restoration, OPcache handling, and HTTP acceptance remain separate
production-window checks.

## Transitional GitHub environment setup

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
- `SFTP_PASSWORD`: password for that account. The workflow exposes it only to a
  short-lived `SSH_ASKPASS` helper on the GitHub runner, never as a command-line
  argument or repository value.
- `SFTP_KNOWN_HOSTS`: pinned `known_hosts` line obtained from the hosting
  provider through a trusted channel. The workflow deliberately does not use
  `ssh-keyscan` at deployment time.

Environment variables:

- `SFTP_PORT`: set to Webglobe's documented port `222` (configured 2026-09-22).
- `SFTP_REMOTE_PATH`: set to `.` after the 2026-09-22 authenticated read-only
  probe confirmed that the restricted account opens at its application-root `/`
  and cannot move above it with `cd ..`.

The account must be restricted to this application and must not provide access
to database data, user uploads outside the application root, or unrelated
hosting content. Test transfer and account restrictions using an isolated target
where practical; verify the exact production paths before the maintenance update.

The earlier direct-runner design used the manual `Verify production SFTP access`
workflow from protected `main`. It authenticates with the production environment,
verifies the pinned host key and target, then runs only `pwd`, `cd ..`, and `pwd`.
It does not list production filenames or issue any write command. A successful
result must show that both working-directory checks remained at the account root
`/`.
The first GitHub-hosted runner attempt, run `35747056690` on 2026-09-22, timed
out while opening TCP port 222 before authentication or any remote command.
Run `35836160474` repeated the same bounded timeout on 2026-09-23 while a
workstation TCP probe succeeded. Webglobe Admin showed no country or IP
restriction on the dedicated account. The reviewed local handoff is therefore
the selected alternative deployment origin.

The existing hosting account accepts password-authenticated SFTP on port 222 and
can enter the configured application target. It authenticates to SSH but the
server disables command execution for that account. Read-only navigation also
confirmed that this legacy account can leave the application target and reach the
wider hosting tree, so it is not accepted as the final deployment identity. Use
a separate account rooted as narrowly as Webglobe supports. An owner-approved
dedicated account was created on 2026-09-22, and the control panel confirms its
application-root mapping plus read, write, delete, listing, directory-change,
directory-create, and rename permissions. Its password remains with the owner and
is stored only as the protected GitHub environment secret `SFTP_PASSWORD`, not in
the repository. An external password-authenticated
read-only SFTP probe opened at `/`; attempting to move to its parent remained at
`/`, so the effective account boundary and deployment target `.` are confirmed.
Write/create/rename/delete behavior was verified from the workstation in a
random isolated directory using merged `main` run `35848005204`; the synthetic
marker and directory were removed successfully. The password and independently
corroborated host key are stored in the protected environment. The first
runner-originated test timed out before authentication, so that network-path
blocker remains.
Maintenance mode, cache refresh,
release cleanup, and
rollback commands need either a separately enabled command-capable account or an
explicit manual WebSSH/control-panel procedure. The earlier probes and capability
inspection did not modify remote data. The approved account creation changed
access configuration only. Public-key testing temporarily created
`.ssh/authorized_keys` inside the restricted account; it and the `.ssh` directory
were removed after the test, and the private keys were destroyed locally. No
application file or production setting was changed.

## Deploying and rolling back

Open a successful `CI` run created by a push to the default branch and copy its
numeric run ID from the URL. Run local `Prepare`, complete the maintenance and
backup prerequisites, then run local `Deploy` with that same ID. Confirm only
the SHA printed by the command and only inside the approved maintenance window.

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

After the first local deployment is accepted, remove the obsolete direct-transfer
workflows and delete the six SFTP secrets/variables from the GitHub `production`
environment. Do not remove them earlier during the handoff. GitHub CI and artifact
construction remain active after this cleanup.
