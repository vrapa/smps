# Completion plan: localization, one public repository, and Webglobe deployment

Updated: 2026-09-16. This is the coordinating execution plan for the final
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
  through a documented, repeatable workflow with protected credentials.
- [ ] Deployment preserves configuration, uploads, photographs, logs, and database
  contents; application rollback has been rehearsed in an isolated test environment and the
  production recovery procedure is ready and verified.
- [ ] The final multilingual release is running on production and its critical
  workflows have passed the post-deployment checks.

One active public repository does not mean publishing private hosting settings
or old GitLab history. Those remain outside the public development repository.

## Verified starting point

Read-only inspection on 2026-09-16 established:

| Area | Evidence and remaining work |
| --- | --- |
| GitHub | Public repository, default branch `main`. Previous verified release `7c960cb` completed CI on 2026-09-11; recheck protections before cutover. |
| Publication | Clean public snapshot and MIT licensing are recorded as complete. Repeat the content audit for the final release. |
| Localization | Infrastructure and shared UI stages are recorded complete; songs/files, concerts/dates, users/settings, and final acceptance remain. Catalogue parity tests exist. |
| GitHub deployment | `.github/workflows/deploy-production.yml` exists, but the environments API returned no environments and no deployment runs were listed. Hosting connectivity is unverified. |
| Current transfer design | SFTP key authentication, default port 22, in-place recursive upload without deletion. No implemented release activation, cache lifecycle, health check, or stale-file reconciliation. |
| Artifact recovery | CI artifacts expire after 14 days; an older artifact alone is not a durable recovery strategy. |
| GitLab | The versioned pipeline still defines automatic deployment from `master`, plain FTP, deletion mirrors, and separate application/vendor jobs. Its current remote operational state was not inspected. |
| Documentation | `AGENTS.md` still describes Nette Database and an ignored lock file, although `composer.lock` is now tracked and deployment documentation records the Doctrine-only transition. Reconcile during cutover. |

Successful artifact construction is not evidence of a successful Webglobe
deployment. No connection to the hosting account was made for this plan.

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

## 1. Verify the Webglobe hosting contract

- [ ] Establish the actual hosting product/platform serving `smps.rkcomputer.cz`.
  Check whether temporary hosting staging is easy to provide; it is optional.
  Record privately the application root, document root, protocol, port, account
  restrictions, and host identity. Do not infer them from the old FTP pipeline.
- [x] Verify noninteractive SFTP password authentication from an external client
  on the provider-documented port and confirm the application target without
  changing remote data.
- [ ] Verify key authentication from the runner network. The current account
  accepts SFTP but rejects SSH command execution after successful authentication;
  browser WebSSH alone is not evidence that unattended commands are available.
- [x] Probe the current legacy deployment endpoint without changing remote data.
  Record only sanitized transport results; keep its host and credentials private.
- [ ] Prefer SFTP/SSH with verified host keys. If this account only supports FTPS,
  specify a TLS-verified FTPS implementation and the necessary CLI/activation
  procedure. If neither can meet the acceptance criteria, document the exact
  hosting change needed before implementation; do not fall back to plain FTP.
- [ ] Check the actual web and CLI PHP versions and extensions, especially
  `pdo_mysql`, `intl`, `mbstring`, and `fileinfo`, against the locked application
  requirements. Check memory, execution/upload limits, timezone, OPcache, disk
  quota, filesystem permissions, and database version. Symlink support is only
  relevant if it simplifies this installation; it is not required.
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

Current transport status (2026-09-22): an authenticated read-only probe of the
legacy deployment endpoint confirmed plain FTP on port 21; standard SSH port 22
and implicit FTPS port 990 were unavailable, and certificate-verified explicit
and implicit FTPS did not succeed. The provider-documented SSH/SFTP port 222 is
reachable. The existing account successfully authenticated over SFTP and changed
into the expected application target. SSH authentication also succeeded, but the
server explicitly disabled command execution for this account. A network-observed
ED25519 fingerprint is retained only as an untrusted diagnostic candidate; obtain
the host key through a trusted provider channel before deployment. No remote data
changed. Before credentials can be configured, verify key authentication and
choose either a separately enabled command-capable account or a documented manual
maintenance/cache procedure alongside SFTP. See
[webglobe-capability-checklist.md](webglobe-capability-checklist.md).

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
- [ ] Add environment-scoped credentials only after the SFTP account, trusted
  host key, and target path have been verified. Never use repository-wide secrets.
- [ ] Use a deployment account scoped as narrowly as the hosting supports. Treat
  wider access as an explicit unresolved constraint; never claim isolation from
  uploads/configuration merely because the upload script excludes those paths.
  The current legacy account can navigate above the application target into the
  wider hosting tree and is therefore not accepted as the final deployment
  account.
- [x] Store the provider-documented SFTP port `222` as a non-secret environment
  variable.
- [x] Verify the SFTP target path with an authenticated read-only probe and store
  it as an environment-scoped variable without exposing credentials.
- [ ] Pin the host key obtained through a trusted provider channel and verify key
  authentication from the runner network. A key learned from the same untrusted
  connection is not sufficient identity evidence.
- [ ] Bind deployment to the trusted CI workflow, repository, successful tested
  commit and artifact digest; select revisions from protected `main`, including
  an explicitly chosen earlier revision for rollback. Do not rebuild dependencies
  or run Composer update on production. Fork PRs receive no deployment secrets.
- [ ] Deploy the same artifact that passed CI and the isolated rehearsal; reuse
  it on hosting staging if available. Make artifact lookup
  explicit about repository and run ID; the existing download command runs
  without a checkout and must be checked in that context.
- [ ] Implement in-place deployment during a planned maintenance window. Keep
  existing configuration and upload locations, block application requests/writes
  independently of the code being replaced, and verify uploaded files before
  reopening the site. Do not require data relocation or a release-directory redesign.
- [ ] Provide a simple recovery path: keep maintenance enabled on failure, restore
  the backed-up compatible code/dependencies and refresh cache, then verify before
  reopening. A manual recovery during the outage is acceptable.
- [ ] Add deployment serialization, disk/permission preflight, a release manifest,
  targeted application-cache invalidation, and hosting-appropriate OPcache handling.
  No public cache-clearing or migration endpoint is introduced.
- [ ] Reconcile obsolete code using the previous release manifest. A no-delete
  overlay leaves obsolete files behind and does
  not by itself provide an exact rollback. Never use broad mirror deletion.
- [ ] Add health checks and a deployed-revision record without sensitive output;
  failed activation leaves or restores the last working compatible version.
- [ ] Keep the new artifact and at least the last working production version,
  including its manifest, outside short-lived CI retention. Back up the actual
  current hosting version before the first update; it may predate the GitHub
  releases. Preserve private configuration/database/upload backups separately and
  verify restoration. A simple private backup location is sufficient.

Gate: an isolated rehearsal demonstrates protected-path preservation, exact revision
identity, interrupted-upload recovery, stale-file handling, and application
rollback. This milestone updates both workflows and [deployment.md](deployment.md).

## 7. Rehearse in an isolated test environment

- [ ] Use an isolated local/CI environment with separate config, database and
  storage. If a temporary Webglobe staging target is easy to provide, use it for
  additional hosting checks; a permanent staging site is not a release requirement.
  Disable real notifications and prevent indexing of any hosted test site. Use
  synthetic data and generated documents rather than production data or scores.
- [ ] Verify fresh schema installation and rehearse the upgrade path against a
  synthetic database matching the existing schema and migration history.
- [ ] Test the known pending schema/configuration transition: obsolete `database:`
  config removal, `users.deprecated_role` removal, and the `skladby.active` type
  normalization. Recheck actual production migration status before scheduling it;
  do not replay the initial baseline over an existing database.
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
- [ ] Deploy the tested artifact in place through the protected production workflow.
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
