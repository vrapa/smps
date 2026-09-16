# Completion plan: localization, one public repository, and Webglobe deployment

Updated: 2026-09-16. This is the coordinating execution plan for the final
state. It takes precedence over the transitional dual-repository workflow in
the earlier plans. Detailed localization checklists remain in
[localization-plan.md](localization-plan.md); the publication record remains in
[public-release-plan.md](public-release-plan.md).

This planning change does not activate hosting services, change credentials,
run migrations, deploy production, or archive either repository.

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
- [ ] A tested GitHub artifact can be deployed to Webglobe staging and production
  through a documented, repeatable workflow with protected credentials.
- [ ] Deployment preserves configuration, uploads, photographs, logs, and database
  contents; application rollback has been demonstrated on staging and the
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

- [ ] Establish the actual hosting product/platform and available staging target.
  Record privately the application root, document root, protocol, port, account
  restrictions, and host identity. Do not infer them from the old FTP pipeline.
- [ ] Verify noninteractive SFTP and key authentication from the runner network,
  and separately verify SSH command execution. Browser WebSSH alone is not
  evidence that unattended deployment is available.
- [ ] Prefer SFTP/SSH with verified host keys. If this account only supports FTPS,
  specify a TLS-verified FTPS implementation and the necessary CLI/activation
  procedure. If neither can meet the acceptance criteria, document the exact
  hosting change needed before implementation; do not fall back to plain FTP.
- [ ] Check the actual web and CLI PHP versions and extensions, especially
  `pdo_mysql`, `intl`, `mbstring`, and `fileinfo`, against the locked application
  requirements. Check memory, execution/upload limits, timezone, OPcache, disk
  quota, filesystem permissions, rename/symlink support, and database version.
- [ ] Verify how the host serves `www/index.php`, honours rewrite/access rules,
  and prevents HTTP access to configuration, vendor code, logs, and backups.
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

## 2. Make GitHub the sole development source

- [ ] Identify the last synchronized source trees and reconcile only reviewed
  pending changes; verify the public tree and commit author identity.
- [ ] Verify a complete confidential GitLab backup, including refs, and identify
  any issues, CI settings, or other service metadata requiring separate retention.
- [ ] Choose a clean GitHub checkout as the development workspace. Preserve all
  ignored local configuration and runtime data in the existing workspace;
  prepare local setup separately without publishing or bulk copying it.
- [ ] Recheck `main` protection, required checks, PR workflow, Actions permissions,
  and the public `noreply` commit identity. Remove operational dependence on the
  old development branch and two-repository copying instructions.
- [ ] Reconcile `AGENTS.md`, README, contribution instructions, and linked plans
  with the actual Doctrine, lock-file, CI, and deployment state.
- [ ] Record a GitLab development freeze. Inventory its triggers, schedules, and
  credentials; establish a controlled deployment handover so both providers
  cannot deploy concurrently. Keep the old deployment only as a restricted
  transitional recovery option until milestone 8.

Gate: the next implementation commit is developed, reviewed, and merged only
on GitHub. Completing translations is not a prerequisite for this cutover.
Archiving the old service and revoking its credentials happen in milestone 9.

## 3. Complete translations in three reviewable increments

- [ ] Songs and files: complete localization-plan stage 3, including upload
  validation, permission failures, categories, deletion/download controls, and
  empty states. Keep existing storage paths and category codes stable.
- [ ] Concerts and dates: complete stage 4 with locale-aware formatting and the
  configured timezone. Add an `intl` requirement only after verifying the
  development, CI, staging, and production environments.
- [ ] Users, roles, and settings: complete stage 5, including password forms,
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
- [ ] Update English and Czech README/setup instructions, supported runtime
  requirements, locale configuration, and translation contribution guidance.
- [ ] Run the full suite, isolated database integration, PHP syntax, PHPCS,
  PHPStan, Latte/NEON checks, Composer validation, and dependency audit.

Gate: no unexplained mixed-language paths remain; automated acceptance and
language-review status are recorded. Hosting acceptance continues in milestone 7.

## 5. Enforce the public/private boundary

- [ ] Re-audit the final tracked tree, all public reachable refs/history, release
  attachments, Actions artifacts/logs, and published screenshots. Review both
  secret patterns and personal/copyrighted content; Gitleaks alone is insufficient.
- [ ] Automate checks rejecting protected configuration, database exports and
  production fixtures, photographs, PDFs/scores, recordings, and uploads from
  first-party public source and release artifacts. Permit only explicitly reviewed
  public assets and necessary licensed dependency content.
- [ ] Check tests use synthetic users and generated upload samples without
  production names, email addresses, hashes, or music documents. Preserve MIT
  attribution and legitimate dependency notices.
- [ ] Harden artifact checks for traversal, symlinks/hardlinks, unexpected paths,
  and secret-bearing configuration; validate before extraction and transfer.
- [ ] Verify private hosting values never enter public workflow output, command
  traces, screenshots, commit messages, or documentation. Redact diagnostic logs.

Gate: the selected release and its public history/artifact pass both automated
scans and the content review. Old private history remains confidential.

## 6. Implement a complete Webglobe deployment and recovery workflow

- [ ] Configure separate `staging` and `production` environments with restricted
  deployment branches and environment-scoped credentials. Agree the reviewer
  arrangement with the owner: preventing self-review requires another eligible
  approver and must not leave a single-maintainer project unable to deploy.
- [ ] Use a deployment account scoped as narrowly as the hosting supports. Treat
  wider access as an explicit unresolved constraint; never claim isolation from
  uploads/configuration merely because the upload script excludes those paths.
- [ ] Parameterize the verified port and target path; pin the trusted host key
  or verify the FTPS certificate. Check connectivity without echoing credentials.
- [ ] Bind deployment to the trusted CI workflow, repository, successful tested
  commit and artifact digest; select revisions from protected `main`, including
  an explicitly chosen earlier revision for rollback. Do not rebuild dependencies
  or run Composer update on production. Fork PRs receive no deployment secrets.
- [ ] Use one tested artifact for staging and production. Make artifact lookup
  explicit about repository and run ID; the existing download command runs
  without a checkout and must be checked in that context.
- [ ] Prefer isolated release directories and an atomic activation if supported.
  Persist configuration/uploads/logs outside disposable releases and explicitly
  test how application-relative upload paths attach to persistent storage.
  Any existing-data relocation needs its own reviewed backup and migration step.
- [ ] If atomic activation is unavailable, implement tested maintenance-mode
  deployment with upload verification, controlled activation, and failure recovery.
  Do not serve a mix of old application code and new vendor files during transfer.
- [ ] Add deployment serialization, disk/permission preflight, a release manifest,
  cache invalidation or per-release cache, and hosting-appropriate OPcache handling.
  No public cache-clearing or migration endpoint is introduced.
- [ ] Reconcile obsolete code using the previous release manifest, or by switching
  complete releases. A no-delete overlay leaves obsolete files behind and does
  not by itself provide an exact rollback. Never use broad mirror deletion.
- [ ] Add health checks and a deployed-revision record without sensitive output;
  failed activation leaves or restores the last working compatible version.
- [ ] Retain the current and at least two previous tested artifacts/manifests for
  at least 90 days in suitable storage, subject to host/account limits. Preserve
  private configuration/database/upload backups separately and verify restoration.

Gate: a staging dry run demonstrates protected-path preservation, exact revision
identity, interrupted-upload recovery, stale-file handling, and application
rollback. This milestone updates both workflows and [deployment.md](deployment.md).

## 7. Rehearse on isolated Webglobe staging

- [ ] Provision staging with separate config, database, storage and credentials;
  prevent indexing and production notifications. Use synthetic data and generated
  documents rather than production dumps, photographs, or copyrighted scores.
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
  production acceptance. Test actual hosting rewrite/access rules.
- [ ] Demonstrate rollback to the previous artifact, then redeploy the candidate;
  include a newly added code file to prove obsolete files do not survive rollback.
- [ ] Simulate failure before activation and verify existing runtime data survives.

Gate: record tested SHA/digest, test outcomes, transport/activation method and
recovery result. Keep hosting identifiers and operational evidence private where
necessary. Green GitHub CI alone does not close this gate.

## 8. Perform the production handover

- [ ] Prepare an owner-reviewed runbook naming the exact tested release, target,
  maintenance window, verified backups, smoke tests, and rollback decision point.
- [ ] Confirm the last GitLab job has finished and freeze its deployment triggers
  before starting GitHub production deployment. Avoid two independent deployers.
- [ ] Back up production config, application release, database, and uploads; verify
  restore access. Confirm the required database migration versions and compatibility
  with both the new release and the rollback release.
- [ ] Apply separately authorized configuration cleanup and CLI migrations, if
  needed. Deployment does not silently run schema changes; database restore is a
  distinct operation and must account for writes since the backup.
- [ ] Deploy the staging-tested artifact through the protected production workflow.
  Preserve `locale: cs` unless an installation-language change is requested.
- [ ] Verify login, roles, representative data, songs/concerts, upload/download,
  settings, cache, and permissions; observe error logs for at least one normal
  operating day. Use only an agreed disposable record for production write checks.
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

Inputs needed at the relevant operational step: the exact Webglobe service and
available access method, a staging target, the production approver arrangement,
and the release/maintenance window. Collect connection credentials via protected
configuration, never by adding them to this plan. No such input is required to
finish planning or continue application translation work.
