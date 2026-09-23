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
`main` run `35838496796`; isolated write rehearsal, release activation, cache
handling, durable artifact
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
until the development, CI, and Webglobe CLI runtimes have all been checked. The
Webglobe web runtime already has Intl enabled.

## Verified hosting runtime and recovery capabilities

The Webglobe web runtime was inspected read-only on 2026-09-22. It uses PHP
8.1.34 through FPM/FastCGI with `pdo_mysql`, `intl`, `mbstring`, `fileinfo`, and
Zend OPcache enabled. The observed 256 MB memory limit, 90-second execution
limit, and 256 MB upload/post limits are compatible with the current application.
The server default timezone differs from the application's explicit
`Europe/Prague` setting, so keep the application setting and verify date handling
during acceptance. CLI PHP, CLI extensions, filesystem permissions, disk
headroom, and database version still require private verification.

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

Select a successful `CI` run created by a push to protected `main`, then use its
numeric run ID in one of these modes:

```powershell
# Validate and audit the exact GitHub artifact; no production connection.
./tools/deploy-production.ps1 -CiRunId 123456789 -Mode Prepare

# Additionally verify password login, target, and chroot using only pwd/cd/pwd.
./tools/deploy-production.ps1 -CiRunId 123456789 -Mode Preflight

# Repeat the preflight, require an exact SHA confirmation, then overlay files.
./tools/deploy-production.ps1 -CiRunId 123456789 -Mode Deploy
```

Every mode requires a clean local checkout of the current GitHub default-branch
head. It verifies the repository, successful completed `CI` push run, default
branch and commit SHA, downloads the existing `smps-<SHA>` artifact, audits the
archive, extracts it into a temporary directory, and rejects protected paths.
Use `-KeepWorkspace` only for local troubleshooting; the default scopes cleanup
strictly to its generated temporary directory.

`Preflight` and `Deploy` let OpenSSH request the password directly in the console;
the script does not receive, store, print, or pass it on the command line.
On Windows the fixed SFTP commands are streamed to a normal interactive session;
the script deliberately does not use `sftp -b`, because batch mode disables the
console password prompt. The exit status, error output, and restricted-root result
are still checked.
`Deploy` prompts twice because it completes and checks a separate read-only
connection before asking for the exact `DEPLOY <short-SHA>` confirmation and
opening the upload connection. The upload uses the reviewed allowlist and no
remote delete command. It does not create backups, enable maintenance, change
`config/local.neon`, run migrations, clear caches, or perform acceptance checks.
Those remain explicit maintenance-window steps.

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
Write/create/rename/delete behavior still needs an isolated test using the
protected password. The password and independently corroborated host key are
stored in the protected environment. The first runner-originated test timed out
before authentication, so that network-path blocker remains.
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
