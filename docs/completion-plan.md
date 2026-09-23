# Completion plan: localization, one public repository, and Webglobe deployment

Updated: 2026-09-23. This is the coordinating execution plan for the final
state. It takes precedence over the transitional dual-repository workflow in
the earlier plans. Detailed localization checklists remain in
[localization-plan.md](localization-plan.md); the publication record remains in
[public-release-plan.md](public-release-plan.md).

This planning change does not activate hosting services, change credentials,
run migrations, deploy production, or archive either repository.

## Confirmed production target and acceptable downtime

The owner confirmed on 2026-09-16 that the existing production application runs
on Webglobe at [https://smps.rkcomputer.cz](https://smps.rkcomputer.cz).
GitHub deployment will update that installation in place, preserving its users,
database, local configuration, photographs, and uploaded documents.
The public website address does not determine the SFTP host or remote path;
verify those separately before uploading.

A planned outage during deployment is acceptable. Use a straightforward
maintenance window: stop application traffic/writes, take a consistent backup,
upload the tested application and dependencies, apply any separately authorized
configuration/schema changes, refresh cache, verify the application, then reopen
the site. If the update fails, keep maintenance enabled while restoring the
previous compatible version. There is no zero-downtime requirement or fixed
maximum outage agreed here.

Atomic release switching, permanent staging infrastructure, automated failover,
and production-grade availability engineering are not prerequisites. Prefer
existing local/CI test environments; use temporary hosting staging if convenient.
Keep practical safeguards: recoverable backups, verified encrypted transfer,
protection of private/runtime data, and a usable rollback procedure. Accepting
downtime does not authorize data loss or deployment as part of this planning edit.

## Final state and completion criteria

- [ ] `vrapa/smps` on GitHub is the sole active source repository. All development,
  issues, pull requests, releases, CI, and deployment start there.
- [ ] The private GitLab repository is a restricted, read-only historical archive
  with its pipelines and deployment credentials retired. A confidential verified
  backup preserves the old history. There is no ongoing mirror or manual copy.
- [ ] The public repository retains MIT licensing and contains no private
  configuration, credentials, production database records, photographs, scores,
  sheet music, recordings, or uploads, including reachable history and public
  release/CI attachments. Approved attribution and third-party notices remain.
- [ ] The complete UI works in Czech, English, German, and Dutch, selected through
  `parameters.locale`, with Czech as the installation default. User content and
  stored role/category identifiers remain unchanged.
- [ ] A tested GitHub artifact can update the existing Webglobe production site
  through a documented, repeatable local handoff. GitHub remains the source of
  the tested artifact; the workstation supplies the working SFTP network path,
  strict host-key verification, and an interactive password prompt.
- [ ] Deployment preserves configuration, uploads, photographs, logs, and database
  contents; application rollback has been rehearsed in an isolated test environment and the
  production recovery procedure is ready and verified.
- [ ] The final multilingual release is running on production and its critical
  workflows have passed the post-deployment checks.

One active public repository does not mean publishing private hosting settings
or old GitLab history. Those remain outside the public development repository.

## Verified starting point

Read-only inspection through 2026-09-22 established:

| Area | Evidence and remaining work |
| --- | --- |
| GitHub | Public repository, default branch `main`, protected required CI, and a branch-restricted `production` environment. Manual dispatch is the approved single-maintainer production gate. |
| Publication | Clean public snapshot and MIT licensing are recorded as complete. Repeat the content audit for the final release. |
| Localization | All three implementation increments and four catalogues are complete. Final real-path, responsive, failure-path, and fluent-speaker acceptance remains. |
| GitHub deployment | `.github/workflows/deploy-production.yml` and the protected `production` environment exist, but two GitHub-hosted runner probes timed out before SSH authentication while a simultaneous workstation TCP probe succeeded. Direct hosted-runner transfer is not the selected handoff path. The GitHub SFTP secrets remain temporarily until the first local deployment is accepted, then must be removed. |
| Current transfer design | GitHub CI builds the tested SHA-named artifact. A local PowerShell command will validate and download that exact artifact, audit it again, and transfer its allowlisted paths over SFTP with the workstation's pinned host identity and an interactive password. The upload is an in-place overlay without deletion. |
| Artifact recovery | CI artifacts expire after 14 days; an older artifact alone is not a durable recovery strategy. |
| GitLab | A sanitized inventory found no active pipeline, schedule, hook, deploy key, or environment, but CI and two runners remain enabled and a future successful default-branch push could still trigger the legacy FTP jobs. Freeze remains pending. |
| Documentation | `AGENTS.md`, README, contribution guidance, and deployment plans are reconciled with Doctrine, the tracked lock file, four locales, and the GitHub-only development flow. |

Successful artifact construction is not evidence of a successful Webglobe
deployment. Hosting inspection and connection probes were read-only except for
the explicitly approved dedicated-account creation and public-key test. The test
`.ssh/authorized_keys` and directory were removed after authentication failed;
no application file, backup, or production data was changed.

## Execution rules

Each coherent code/documentation step gets its own reviewed commit and push;
update the corresponding checklist with evidence, remaining work, and any
blocker. Use the protected public pull-request flow and preserve required CI
checks. Do not mark a hosting milestone complete from local tests alone.

Until milestone 2 completes, use the existing transfer process for reviewed
changes only. After milestone 2, commit and push solely from a checkout of the
clean GitHub history. Never merge, fetch for publication, or mirror private
GitLab history into the public repository.

Production deployment, production configuration edits, database migrations,
paid hosting activation, and retirement of the old deployment are separately
identified operational actions. Prepare their exact revision, target, backup,
test evidence, and rollback before requesting any outstanding authorization.
Keep credentials and private connection details in protected operational
storage; public progress notes contain only sanitized results.

## Approved local deployment handoff

The owner selected the following transition on 2026-09-23 after the second
GitHub-hosted runner probe timed out while the same Webglobe SFTP port remained
reachable from the development workstation:

1. [x] Add a Windows PowerShell deployment command and ignored local configuration
   template. It must accept an explicit successful `CI` push run ID from protected
   `main`, verify the repository/run/status/SHA, download the existing SHA-named
   artifact, run `tools/audit_public_content.py archive`, and extract only after
   that audit succeeds.
2. [x] Add separate prepare, read-only preflight, and deployment modes. SFTP must
   use the already corroborated local `known_hosts` entry with strict checking,
   prompt for the dedicated account password interactively, and upload the same
   no-delete allowlist as the reviewed workflow. It must not store or print the
   password, run migrations, clear caches, edit protected configuration, or
   delete remote files.
3. [x] Validate PowerShell syntax and failure handling, then run prepare mode
   against a successful current `main` CI artifact. Run `35838496796` produced
   revision `077b723ba21bb575b9cc1cd72eb313218c7f5c40`; local prepare verified
   its metadata, ancestry, archive policy, extraction, and SHA-256 digest
   `b3ba991d045928ba176d28bfa3c61ebf6d4381174b1337933fc01b69f15a0d48`
   without connecting to production.
4. [x] Create the ignored local connection record and run the read-only SFTP
   preflight. Password authentication, strict host-key verification, target `.`,
   and the restricted root were verified from the workstation; the command issued
   only `pwd`, `cd ..`, `pwd`, and `quit` and reported no remote write.
5. [x] Verify write/create/rename/delete behavior only in an explicitly approved
   isolated remote test directory before any application upload. The owner ran
   `WriteTest` from the successful merged `main` CI run `35848005204`; the
   synthetic marker and random test directory were removed without touching
   application files.
6. [ ] Rehearse update and recovery, prepare fresh backups and maintenance, and
   request explicit approval for the exact production SHA and target. The first
   production upload is followed immediately by application and protected-path
   acceptance checks; failure keeps maintenance enabled for recovery.
7. [ ] Only after that deployment is accepted, disable/remove the direct GitHub
   SFTP deployment and verification workflows and delete `SFTP_HOST`,
   `SFTP_USERNAME`, `SFTP_PASSWORD`, `SFTP_KNOWN_HOSTS`, `SFTP_PORT`, and
   `SFTP_REMOTE_PATH` from the GitHub `production` environment. Retain GitHub CI
   and artifact construction. Verify the removed secrets are no longer referenced
   before marking this cleanup complete.

The ignored local deployment configuration may contain the non-password
connection identity and target. The password remains only in the operator's
interactive SFTP prompt. A local Docker container is unnecessary: it would use
the same workstation network path while adding another credential and host-key
boundary.

Implementation status (2026-09-23): `tools/deploy-production.ps1`,
`config/deploy.example.psd1`, its Git ignore rule, CI syntax/safety checks, and
the operator documentation are implemented. The merged `main` CI, local
`Prepare`, and password-authenticated read-only `Preflight` passed for the
revision recorded above. The local connection record is ignored and contains no
password. No production write was made by these steps.
An initial local `Preflight` attempt confirmed that Windows OpenSSH suppresses
password prompting when `sftp -b` is used and therefore failed authentication
before any remote command. The command now streams the fixed command list into a
normal interactive SFTP session so OpenSSH can read the password directly from
the console. The corrected command passed CI and the repeated preflight verified
the restricted root without a remote write.
The owner approved and completed the isolated write/create/rename/delete check.
The dedicated `WriteTest` mode limited it to one random
`.smps-deploy-check-*` directory and one synthetic marker, with an exact
confirmation and explicit cleanup. It passed from merged `main` CI run
`35848005204`; no application file was read or changed.
The `Rehearse` mode uses only a synthetic local installation. It passed for
merged `main` CI run `35849397823`, revision
`42ab07fa20416be989b1c6ea9536cb4e4ea78c88`, and artifact SHA-256
`36bec7be0ada889487b323e8dafd16ca9ae6c848b6c84070adfac10901a5feff`.
The run verified candidate overlay, byte-identical preservation of ignored
runtime paths, explicit no-delete stale-file behavior, and exact snapshot
rollback without opening a production connection.

## 1. Verify the Webglobe hosting contract

- [x] Establish the actual hosting product/platform serving `smps.rkcomputer.cz`
  as managed Webglobe Webhosting Plus. Record privately the application root,
  current document root, protocol, port, and account restrictions. The SFTP
  ED25519 fingerprint was independently matched from the development workstation
  and an authenticated temporary WebSSH session; its public key still has to be
  stored in the protected GitHub environment.
- [ ] Check whether temporary hosting staging is easy to provide; it is optional.
- [x] Verify noninteractive SFTP password authentication from an external client
  on the provider-documented port and confirm the application target without
  changing remote data.
- [x] Test public-key authentication for the dedicated account. The server found
  the installed ED25519 and RSA public keys but rejected their signed login,
  including modern and legacy RSA signatures. Treat key authentication as
  unsupported for this virtual FTP/SFTP account and use its protected password.
- [x] Probe the current legacy deployment endpoint without changing remote data.
  Record only sanitized transport results; keep its host and credentials private.
- [ ] Prefer SFTP/SSH with verified host keys. If this account only supports FTPS,
  specify a TLS-verified FTPS implementation and the necessary CLI/activation
  procedure. If neither can meet the acceptance criteria, document the exact
  hosting change needed before implementation; do not fall back to plain FTP.
- [x] Check the actual web PHP runtime against the locked application requirements.
  It runs PHP 8.1.34 through FPM with `pdo_mysql`, `intl`, `mbstring`, `fileinfo`,
  and OPcache enabled; its inspected memory, execution, upload, and post limits
  are sufficient for the current application. The server default timezone differs
  from the application's explicit `Europe/Prague` setting.
- [ ] Check CLI PHP and extensions, filesystem permissions, disk headroom, and
  database version. Symlink support is only relevant if it simplifies this
  installation; it is not required.
- [ ] Verify how the host serves `www/index.php`, honours rewrite/access rules,
  and prevents HTTP access to configuration, vendor code, logs, and backups.
  The tested artifact must contain the project-root fail-closed guard, but the
  hosting document root still has to be set to `www` and verified independently.
- [ ] Select the release activation strategy from milestone 6 using these facts.
  Define how maintenance, cache refresh, and rollback can actually be performed.

Deliverable: sanitized capability checklist plus a private connection record.
Gate: the selected transport and activation mechanism are demonstrably supported
on this hosting account. Translation work can proceed while this is verified.

Webglobe documents browser SSH sessions lasting one hour and a paid permanent
option, and mentions `authorized_keys` and version-specific PHP commands. These
are provider capabilities to verify for the account, not assumptions about the
existing subscription. See the official
[WebSSH instructions](https://www.webglobe.cz/poradna/jak-se-dostanu-do-webove-ssh-konzole)
and [hosting feature list](https://www.webglobe.cz/webhosting), checked 2026-09-16.

Current web-root status (2026-09-17): an unauthenticated production check found
that existing project-root files were being served directly. A reviewed
project-root `.htaccess` guard was uploaded as an emergency mitigation after the
previous file was backed up. The login page remained available, while direct
requests for configuration, dependency, metadata, log, and Git paths returned
HTTP 403. The permanent hosting acceptance item remains open until the document
root points to `www` and the complete access boundary is rechecked.

The 2026-09-22 control-panel inspection confirmed that the `smps` subdomain
currently targets the application root and that its editable directory mapping
can be changed to the application's `www` directory. No setting was changed.
Make that correction only in the approved maintenance window, after backups and
with immediate HTTP/access-boundary verification.

Current transport status (2026-09-22): an authenticated read-only probe of the
legacy deployment endpoint confirmed plain FTP on port 21; standard SSH port 22
and implicit FTPS port 990 were unavailable, and certificate-verified explicit
and implicit FTPS did not succeed. The provider-documented SSH/SFTP port 222 is
reachable. The existing account successfully authenticated over SFTP and changed
into the expected application target. SSH authentication also succeeded, but the
server explicitly disabled command execution for this account. The observed
ED25519 fingerprint was subsequently matched from the authenticated Webglobe
WebSSH environment, giving an independent provider-side path to the same key.
The fingerprint is retained privately and the exact `known_hosts` entry still
has to be stored in the protected GitHub environment. No remote data changed.
Before deployment, configure and verify protected password authentication and
choose either a separately enabled command-capable account or a documented manual
maintenance/cache procedure alongside SFTP. See
[webglobe-capability-checklist.md](webglobe-capability-checklist.md).

The 2026-09-22 control-panel inspection also confirmed that a separate FTP/SFTP
account can be rooted at the application directory with granular file and
directory permissions. The owner subsequently approved creation of that account;
the control panel now confirms its application-root mapping and all seven required
file/directory permissions. An external password-authenticated read-only SFTP
probe then opened at `/`; `cd ..` remained at `/`, confirming the account's
effective application-root boundary. The account-relative deployment target is
therefore `.`. Write/create/rename/delete behavior was subsequently verified in
an isolated random directory and the test data was removed. ED25519 and RSA
public-key login attempts reached signature
verification but were rejected by the provider's `mod_sftp` endpoint, so the
approved workflow design uses the dedicated account password without exposing it
on the command line. The temporary public-key files were removed from the account,
and their private counterparts were destroyed locally. Browser WebSSH was
activated temporarily for the independent host-key check; a permanent console is
a separate paid option and was not activated. Provider-managed
daily FTP and database backups are available, but a fresh independently verified
pre-deployment backup and a recovery rehearsal remain required.

## 2. Make GitHub the sole development source

- [x] Identify the last synchronized source trees and reconcile only reviewed
  pending changes; verify the public tree and commit author identity.
- [x] Verify a complete confidential GitLab backup, including refs, and identify
  any issues, CI settings, or other service metadata requiring separate retention.
- [x] Choose a clean GitHub checkout as the development workspace. Preserve all
  ignored local configuration and runtime data in the existing workspace;
  prepare local setup separately without publishing or bulk copying it.
- [x] Recheck `main` protection, required checks, PR workflow, Actions permissions,
  and the public `noreply` commit identity. Remove operational dependence on the
  old development branch and two-repository copying instructions.
- [x] Reconcile `AGENTS.md`, README, contribution instructions, and linked plans
  with the actual Doctrine, lock-file, CI, and deployment state.
- [x] Inventory the legacy GitLab deployment triggers, schedules, hooks, runners,
  environments, deploy keys, and credential metadata without exposing private
  values. Record only the sanitized result in `gitlab-handover.md`.
- [ ] Record a GitLab development freeze and establish a controlled deployment
  handover so both providers cannot deploy concurrently. Keep the old deployment
  only as a restricted transitional recovery option until milestone 8.

Gate: the next implementation commit is developed, reviewed, and merged only
on GitHub. Completing translations is not a prerequisite for this cutover.
Archiving the old service and revoking its credentials happen in milestone 9.

Current status (2026-09-16): the reviewed private and public tips resolve to
the same tree `58494f0bbfc00458168d12fe6b3f3556505a00d7`. A confidential backup
retains all four local branches and their remote-tracking refs; the unrelated
invalid zero-valued Codex checkpoint ref is not a source ref. The existing
workspace now uses the public GitHub metadata on `main`, while ignored local
configuration, Phinx configuration, and `www/dokumenty` remain in place.
GitHub is public, `main` has strict required CI checks, administrator enforcement,
linear history, conversation resolution, and force-push/deletion protection;
Actions has read-only default token permissions. The approved GitHub `noreply`
identity is configured. This cutover update is developed only on a GitHub branch.
The legacy GitLab inventory found no active pipeline, schedule, hook, deploy key,
or deployment environment, but CI remains enabled and a future successful push
to its protected default branch could still reach two automatic FTP deployment
jobs. Three legacy FTP variables and two active runners remain. The exact values,
private host details, and private commit identifiers were not copied into public
documentation. The remaining milestone-2 action is the separately authorized
GitLab freeze before production handover; GitLab is no longer a development
source. See [gitlab-handover.md](gitlab-handover.md).

## 3. Complete translations in three reviewable increments

- [x] Songs and files: complete localization-plan stage 3, including upload
  validation, permission failures, categories, deletion/download controls, and
  empty states. Keep existing storage paths and category codes stable.
- [x] Concerts and dates: complete stage 4 with locale-aware formatting and the
  configured timezone. Add an `intl` requirement only after verifying the
  development, CI, production, and any optional hosting staging environment.
- [x] Users, roles, and settings: complete stage 5, including password forms,
  validation, notifications, and translated labels for unchanged role codes.

Gate for each increment: meaningful all-locale tests and required CI pass;
the matching detailed checklist is updated and the PR merged.

## 4. Verify the whole multilingual application

- [ ] Audit all user-facing paths, including framework/client validation,
  interpolation, plural wording, accessibility, flash messages, and upload errors.
  Enforce catalogue keys/placeholders and catch new untranslated UI text in CI.
- [ ] Exercise real presenter responses and navigation in all four locales,
  not just catalogue lookups: login/logout, forms, CSRF, 403/404/405/410, generic
  4xx, 500, and maintenance 503. Verify locale propagation on actual failure paths;
  the current 503 template requires a supplied translator for localized text.
- [ ] Review narrow-screen layouts and longer German/Dutch labels. Check public
  instance branding and behaviour when installation-specific photographs are
  absent; do not ship personal photos to make a demo work.
- [ ] Obtain and record fluent-speaker review, including English terminology.
  If a reviewer is unavailable, record that gate as pending.
- [x] Update English and Czech README/setup instructions, supported runtime
  requirements, locale configuration, and translation contribution guidance.
- [x] Run the full suite, isolated database integration, PHP syntax, PHPCS,
  PHPStan, Latte/NEON checks, Composer validation, and dependency audit.

Gate: no unexplained mixed-language paths remain; automated acceptance and
language-review status are recorded. Hosting acceptance continues in milestone 7.

Current status (2026-09-16): automated acceptance renders real presenter
responses for the login page and navigation shell, unauthenticated dashboard
redirect, authenticated empty dashboard, forwarded 403/404/405/410/generic 4xx,
and the 500 callback in `cs`, `en`, `de`, and `nl`. The dashboard now discovers
installation-managed carousel images at
runtime and shows a localized welcome without broken image links when none are
present; private filenames are no longer embedded in public templates. Remaining
work includes submitted validation/CSRF and database-backed paths, maintenance
503 delivery, responsive German/Dutch review, fluent-speaker review, and
hosting acceptance.

## 5. Enforce the public/private boundary

- [ ] Re-audit the final tracked tree, all public reachable refs/history, release
  attachments, Actions artifacts/logs, and published screenshots. Review both
  secret patterns and personal/copyrighted content; Gitleaks alone is insufficient.
- [x] Automate checks rejecting protected configuration, database exports and
  production fixtures, photographs, PDFs/scores, recordings, and uploads from
  first-party public source and release artifacts. Permit only explicitly reviewed
  public assets and necessary licensed dependency content.
- [x] Check tests use synthetic users and generated upload samples without
  production names, email addresses, hashes, or music documents. Preserve MIT
  attribution and legitimate dependency notices.
- [x] Harden artifact checks for traversal, symlinks/hardlinks, unexpected paths,
  and secret-bearing configuration; validate before extraction and transfer.
- [ ] Verify private hosting values never enter public workflow output, command
  traces, screenshots, commit messages, or documentation. Redact diagnostic logs.

Gate: the selected release and its public history/artifact pass both automated
scans and the content review. Old private history remains confidential.

Current status (2026-09-16): `tools/audit_public_content.py` now checks the
tracked tree and every reachable public commit in CI, with an explicit allowlist
for the reviewed favicon and third-party jQuery UI icon sprites. The same policy
validates the deploy archive when it is built and again before extraction,
rejecting unsafe paths, duplicate entries, links/special files, unexpected roots,
protected configuration/runtime paths, and unapproved first-party media or
database/document formats. A current-state review at `fd17c4e` also found no
tags, releases, deployment runs, public issue/PR attachments, risky public-text
patterns, or sensitive patterns in the successful `main` CI log; its downloaded
artifact passed with the digest recorded in `public-release-audit.md`. Remaining
work is the final repeat against the exact production SHA, individual disposition
or expiry of older Actions artifacts, and review of the first deployment log for
hosting-value disclosure.

Test-data review (2026-09-16): integration identities are generic synthetic
records, literal addresses use the reserved `example.test` domain, upload tests
generate short marker strings rather than real scores or recordings, and the
single static bcrypt compatibility value is explicitly documented as generated
and non-production. `PublicTestDataAuditTest` now enforces these verifiable
invariants and rejects document/media files anywhere under `tests/`.

Dependency review (2026-09-22): CI detected critical advisory CVE-2026-79752 in
the Phinx-transitive CakePHP Database 4.5.7 dependency. The four related CakePHP
packages were updated together to 4.6.5; the locked online audit then reported no
known vulnerability advisory, and the complete local test and quality suite
passed.

## 6. Implement a complete Webglobe deployment and recovery workflow

- [x] Configure a `production` environment restricted to protected branches and
  record the owner-approved single-maintainer model. There is no required reviewer
  while the repository has only one eligible administrator; manual dispatch is
  the deliberate approval action. Add `staging` only if it is actually used.
- [x] Add the host, dedicated username, password, and independently corroborated
  host key as environment-scoped secrets after verifying the account and target.
  Their names and update times were read back without exposing values. Never use
  repository-wide secrets.
- [x] Create a dedicated deployment account mapped by the control panel to the
  application directory with the required granular read, write, delete, list,
  directory-change, directory-create, and rename permissions. Its password was
  entered and retained by the owner; it is stored only as the protected GitHub
  environment secret `SFTP_PASSWORD`, not in the repository.
- [x] Verify the dedicated account's effective SFTP root from an external client.
  A password-authenticated read-only probe opened at `/`, and `cd ..` remained at
  `/`; the account is chrooted to the application directory. The legacy account
  remains rejected because it can navigate into the wider hosting tree.
- [x] After the protected password is configured, verify write, create, rename,
  and delete behavior in an isolated test directory before allowing deployment
  to update production files. The owner completed the bounded `WriteTest` from
  merged `main` run `35848005204`; its synthetic marker and directory were
  removed successfully.
- [x] Store the provider-documented SFTP port `222` as a non-secret environment
  variable.
- [x] Verify the SFTP target path with an authenticated read-only probe. For the
  dedicated chrooted account, the account-relative application root is `.`.
- [x] Store the verified `SFTP_REMOTE_PATH=.` value as an environment-scoped
  variable without exposing credentials.
- [x] Pin the independently corroborated host key in the protected environment.
  The fingerprint matched from both the development workstation and authenticated
  Webglobe WebSSH, and the secret name was read back successfully.
- [x] Resolve the unusable GitHub-hosted runner transfer path by selecting the
  verified workstation origin. Run `35747056690` timed out at TCP connection
  setup before authentication; Webglobe Admin showed that all countries and IPs
  are allowed for the account. A second run, `35836160474` on 2026-09-23, failed
  with the same bounded TCP timeout while the port succeeded from the
  workstation. The manual `.github/workflows/verify-production-sftp.yml`
  workflow is therefore retired and now points operators to the reviewed local
  preflight command instead of attempting another hosted-runner SFTP session.
  The current handoff uses the workstation as deployment origin; its
  password-authenticated read-only preflight verified the pinned host, target,
  and chroot. Provider investigation remains optional.
- [x] Bind the local deployment command to the trusted GitHub repository,
  successful `CI` push run, protected default branch, tested commit, SHA-named
  artifact, and current trusted local policy checkout. Do not rebuild dependencies
  or run Composer update during deployment. Local prepare passed against the
  merged `main` artifact from run `35838496796`.
- [ ] Deploy the same artifact that passed CI and the isolated rehearsal; reuse it
  on hosting staging if available. Make artifact lookup explicit about repository
  and run ID and audit it again before extraction and transfer.
- [ ] Implement in-place deployment during a planned maintenance window. Keep
  existing configuration and upload locations, block application requests/writes
  independently of the code being replaced, and verify uploaded files before
  reopening the site. Do not require data relocation or a release-directory redesign.
- [ ] Provide a simple recovery path: keep maintenance enabled on failure, restore
  the backed-up compatible code/dependencies and refresh cache, then verify before
  reopening. A manual recovery during the outage is acceptable.
- [x] Add a release manifest and revision marker. CI and the local handoff verify
  exact artifact path coverage, every file digest, and the selected successful
  `main` revision before any transfer.
- [x] Add deployment serialization so concurrent local operations cannot overlap.
- [x] Add a release-bound maintenance marker with exact confirmations and remote
  read-back. The versioned static HTTP 503 response remains available while the
  application and dependencies are overwritten; deployment refuses to start
  without the marker for the selected release.
- [ ] Add disk/permission preflight, targeted application-cache invalidation, and
  hosting-appropriate OPcache handling. No public cache-clearing or migration
  endpoint is introduced.

Serialization/read-back implementation status (2026-09-23): the local command
now holds an exclusive operating-system file lock for its full run. After an
approved upload, it downloads the remote revision marker and manifest through the
same strict SFTP session and requires the expected revision plus an identical
manifest digest before reporting success. The implementation is merged; live
upload verification remains pending until an exact deployment is separately
approved. Disk headroom, cache/OPcache handling, and full remote-file verification
remain open.

Maintenance implementation status (2026-09-23): `MaintenanceOn` atomically
creates `.maintenance/`, then uploads and reads back a marker containing the
exact tested release SHA; it refuses to overwrite an existing maintenance
operation. `www/.htaccess` returns a dependency-free static HTTP 503 while that
directory exists under either the legacy application-root mapping or the required
`www` document root. Deploy requires the same marker. `MaintenanceOff` refuses
to remove an unknown or different-release marker and requires an exact
confirmation before removing only the marker and empty directory. Live
enablement, 503 verification, upload, and reopening remain production-window
actions. An isolated Apache test passed with both the required `www` document
root and the legacy application-root mapping; the latter also retained HTTP 403
for direct protected-configuration access.
- [ ] Reconcile obsolete code using the previous release manifest. A no-delete
  overlay leaves obsolete files behind and does
  not by itself provide an exact rollback. Never use broad mirror deletion.
- [x] Add a read-only HTTPS health/access-boundary command that does not follow
  off-origin redirects or read response bodies. Baseline mode checks availability,
  a public asset, and denial of protected paths; acceptance mode additionally
  checks anonymous sign-in and denial of direct `/www/index.php` access.
- [ ] Run HTTP acceptance after the exact upload and pair it with the authenticated
  SFTP revision/manifest read-back. Failed activation leaves maintenance enabled
  and restores the last working compatible version.
- [ ] Keep the new artifact and at least the last working production version,
  including its manifest, outside short-lived CI retention. Back up the actual
  current hosting version before the first update; it may predate the GitHub
  releases. Preserve private configuration/database/upload backups separately and
  verify restoration. A simple private backup location is sufficient.

Webglobe currently provides daily FTP snapshots and daily database backups, plus
manual preparation/download and restore controls. These improve recovery options
but do not close this item: request or create fresh backups in the maintenance
window, preserve an independent copy where practical, and verify the exact
restore procedure before replacing production files.

Backup status (2026-09-23): the owner reports creating a fresh production
database backup independently. Its contents and location are intentionally not
recorded in the public repository. The production configuration, application
release and uploads still require a fresh pre-deployment backup, and database
restore access/compatibility still requires verification in the maintenance
window.

Release-identity status (2026-09-23): CI generates a
`RELEASE_SHA` marker and a SHA-256 manifest covering every other regular artifact
file. The public-content archive audit verifies the marker format, exact manifest
path set, and every digest; the local handoff additionally requires the marker to
match the selected successful CI revision and uploads both metadata files. Merged
`main` run `35858438441` produced revision
`3a88ab655310d72af99043eb9ece35f6e685cd61` with archive SHA-256
`3270c4fb21ed549a7eac0718b689f873dc52e472702755dc7830663feba1f4c7`.
The archive audit and local synthetic overlay/rollback rehearsal both passed.
During implementation the manifest exposed that the old broad tar exclusion for
`log` also removed `vendor/psr/log`; the exclusion was removed and CI now verifies
the dependency is retained. Deployment serialization is implemented. A read-only
pre-deployment baseline and acceptance probe passed on 2026-09-23 with a
same-origin login redirect, the sign-in page and a public asset at HTTP 200, and
protected paths plus `/www/index.php` returning only HTTP 403/404. Post-upload
acceptance, cache handling, and stale-file reconciliation remain open.

Gate: an isolated rehearsal demonstrates protected-path preservation, exact revision
identity, interrupted-upload recovery, stale-file handling, and application
rollback. This milestone updates both workflows and [deployment.md](deployment.md).

## 7. Rehearse in an isolated test environment

- [x] Use an isolated local environment with separate synthetic configuration
  and storage to rehearse the exact tested artifact overlay and snapshot rollback.
  Run `35849397823` verified protected-path preservation, explicit no-delete
  stale-file behavior, and an exact restored file/hash manifest without real
  notifications, production data, scores, credentials, or a production connection.
- [x] Extend the isolated environment to its database, verify fresh schema
  installation, and rehearse the upgrade path against a synthetic database
  matching the known schema and migration history.
- [x] Test the database portion of the pending transition: current application
  compatibility with `users.deprecated_role` plus `skladby.active` as `BIT(1)`,
  removal/normalization by the two pending migrations, and sentinel-data
  preservation.
- [ ] Complete the real presenter-flow rehearsal. If a temporary Webglobe staging
  target is easy to provide, use it for additional hosting checks; a permanent
  staging site is not a release requirement. Disable real notifications and
  prevent indexing of any hosted test site.
- [ ] Recheck actual production migration status and remove only the obsolete
  `database:` block from protected production configuration before scheduling
  the two transition migrations. Do not replay the initial baseline over an
  existing database.

Implementation status (2026-09-23): CI now prepares a local `_test` database in
the known pre-transition state, including a synthetic `deprecated_role`, a
`BIT(1)` song flag, sentinel records, and Phinx history immediately before the
two pending migrations. It exercises representative Doctrine reads/writes before
the migrations, applies only the missing transition versions, and then verifies
the normalized schema, preserved sentinel data, Doctrine schema, and full test
suite. Merged `main` run `35850585556` passed every phase for revision
`6603e41b8dd7b7d1502fda869884cee8bc4d5e9d`. The protected production
configuration cleanup, actual production migration status, and any production
migration remain manual maintenance-window checks.
- [ ] Deploy the candidate, exercise all locales, roles, password changes, song
  and concert flows, upload/download/delete, pagination, dates, and error responses.
- [ ] Check unauthenticated access to upload URLs as well as presenter download
  authorization. If direct file access bypasses authentication, fix it before
  production acceptance. Test actual hosting rewrite/access rules on temporary
  hosting staging or during the production maintenance window before reopening.
- [ ] Demonstrate rollback to the previous artifact, then redeploy the candidate;
  include a newly added code file to prove obsolete files do not survive rollback.
- [ ] Simulate failure before activation and verify existing runtime data survives.

Gate: record tested SHA/digest, test outcomes, update method and recovery result.
If rehearsal was local, explicitly carry hosting-specific checks into milestone 8.
Keep private connection details and operational evidence outside public records.

## 8. Perform the production handover

- [ ] Prepare an owner-reviewed runbook naming the exact tested release, target,
  maintenance window, verified backups, smoke tests, and rollback decision point.
  The target is the existing `https://smps.rkcomputer.cz` installation. Plan for
  downtime covering backup, transfer, any approved migrations, and verification.
- [ ] Confirm the last GitLab job has finished and freeze its deployment triggers
  before starting GitHub production deployment. Avoid two independent deployers.
- [ ] Enable maintenance and stop application/background writes, then back up
  production config, application release, database, and uploads; verify
  restore access. Confirm the required database migration versions and compatibility
  with both the new release and the rollback release.
- [ ] Apply separately authorized configuration cleanup and CLI migrations, if
  needed. Deployment does not silently run schema changes; database restore is a
  distinct operation and must account for writes since the backup.
- [ ] Deploy the tested artifact in place through the reviewed local deployment
  command after its production confirmation prompt.
  Preserve `locale: cs` unless an installation-language change is requested.
- [ ] Verify startup, login, roles, representative data, songs/concerts,
  upload/download, settings, cache, and permissions. Complete hosting-specific
  checks carried over from rehearsal, then disable maintenance and confirm the
  public URL works. Briefly review error logs; a mandatory full-day observation
  period is not a prerequisite. Use an agreed disposable record for write checks.
- [ ] Record deployment outcome and rollback readiness. If acceptance fails, execute
  the prepared compatible rollback and keep this milestone open.

Gate: the owner accepts the working multilingual release on Webglobe and the
GitHub deployment/recovery procedure. No production data is published or lost.

## 9. Retire GitLab and close the transition

- [ ] Refresh and verify the confidential historical backup and agreed retention.
- [ ] Disable old CI triggers/schedules/webhooks, revoke the old deploy credentials,
  and archive GitLab read-only. Verify revocation cannot disable the new account.
- [ ] Remove obsolete GitLab pipeline/scripts from active GitHub source and update
  documentation, badges, remotes, and workspace instructions accordingly.
- [ ] Mark earlier transition plans complete with links to sanitized verification
  evidence; keep outstanding review items open rather than declaring them done.
- [ ] Verify a fresh clone of GitHub installs and tests successfully and that the
  documented next release can be deployed without GitLab access.

Gate: GitHub is the only active repository and deployment source. The retained
private archive is a backup, not a second place for ongoing development.

## Recommended order and outstanding inputs

Start milestones 1 and 2, then complete the three translation increments.
Deployment implementation can proceed once hosting capabilities are verified.
Milestones 4–7 must pass before production handover; retire GitLab last.

The production URL and acceptance of planned downtime are confirmed. Inputs
needed at the relevant operational step: the exact Webglobe service and access
method, the production approver arrangement, and the release/maintenance window.
An optional temporary staging target may be used if readily available.
Collect connection credentials via protected
configuration, never by adding them to this plan. No such input is required to
finish planning or continue application translation work.
