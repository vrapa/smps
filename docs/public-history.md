# Clean public Git history

The public GitHub repository should start from a reviewed snapshot rather than
publishing or rewriting the existing private GitLab history. The private
history contains removed deployment material and personal metadata that is not
needed to build or operate the released application.

This procedure preserves the original repository as a restricted archive and
creates one new root commit for GitHub. It must not force-push, replace, or
delete any GitLab ref.

The bundle and snapshot commands were dry-run at `86e0b52` in a temporary
directory. The verified bundle opened successfully, and the 159-file snapshot
contained no forbidden configuration, uploads, dependencies, or runtime data.
The temporary files were deleted. Separate retained bundles containing all
private refs were subsequently created and verified outside the repository,
including the tested release source on 2026-09-11. Their locations and SHA-256
records are kept in the private operational record and must not be copied into
public release material.

## Preconditions

Do not start the migration until all of these are true:

- The release commit is identified and its working tree is clean.
- The MIT copyright holder and year are confirmed and `LICENSE` is committed.
- The ignored, production-managed choir photographs are absent from the
  release tree and public snapshot.
- The final tracked-tree and full-history secret scans pass.
- A complete fresh-database strategy and required release verification are
  approved according to `docs/public-release-plan.md`.
- The GitHub owner, repository name, and public commit author identity are
  confirmed. Use an approved organizational or GitHub `noreply` address rather
  than copying a private email address automatically.

The approved public root-commit author is `Radovan Kraus`. Its email is the
GitHub-provided `28861508+vrapa@users.noreply.github.com` address for account
`vrapa`; do not copy the author email from the private GitLab history.

## Preserve the private history

Before creating the public snapshot, make a full Git bundle from the private
repository and verify it from a separate restricted location:

```powershell
git bundle create <restricted-backup-path> --all
git bundle verify <restricted-backup-path>
Get-FileHash -Algorithm SHA256 <restricted-backup-path>
```

The bundle contains all private refs and metadata. Store it as confidential
backup material; never attach it to GitHub, CI artifacts, tickets, or public
release files. Record its location and checksum in the private operational
record, not in this repository.

## Create the snapshot repository

Export only the tree of the approved release commit into a new empty directory
outside both repositories. Do not copy `.git`, ignored files, local
configuration, uploads, logs, temporary data, or dependency caches.

```powershell
git archive --format=zip --output=<temporary-snapshot.zip> <release-commit>
Expand-Archive -LiteralPath <temporary-snapshot.zip> -DestinationPath <new-repository-directory>
```

Before initializing Git, verify that the snapshot contains no `.git`,
`config/local.neon`, `config/phinx.php`, legacy `config/phinx.yaml`,
`www/dokumenty`, `vendor`, or runtime
files under `log` and `temp` other than their tracked placeholder `.gitignore`
files. Repeat Gitleaks against this exact directory and compare a SHA-256 file
manifest with a separately exported copy of the approved release tree.

Only after those checks pass:

```powershell
git init --initial-branch=main <new-repository-directory>
git -C <new-repository-directory> config user.name <approved-public-name>
git -C <new-repository-directory> config user.email <approved-public-email>
git -C <new-repository-directory> add --all
git -C <new-repository-directory> commit -m "Initial public release"
```

Run Gitleaks over the new one-commit history and verify the commit tree against
the approved snapshot before adding any remote.

## Private GitHub staging first

Create the GitHub repository as private initially. Push only the new `main`
branch from the snapshot repository; never add the GitLab repository as a
source remote and never push tags or refs from the private history.

The private target `vrapa/smps` was created empty on 2026-08-27. On 2026-09-11,
the reviewed snapshot was pushed as its sole `main` root commit without any
GitLab branch, tag, remote, or historical object. Its private CI and downloaded
SHA-named deploy artifact were verified successfully.

Run CI and inspect the artifact while the repository remains private. Do not
configure production credentials or invoke the manually dispatched production
workflow at this stage. GitHub Free, Pro, and Team plans provide required
environment reviewers only to public repositories, and GitHub Free does not
provide environment secrets to private repositories. Moving production
credentials to repository-wide secrets would weaken the intended boundary and
is not an acceptable workaround. See
[GitHub's environment availability notes](https://docs.github.com/en/actions/how-tos/deploy/configure-and-manage-deployments/manage-environments).

After private CI passes, repeat the exact-snapshot scans. GitHub Free does not
allow branch protection on a private repository, so change visibility to
public only after every other non-production publication gate is complete and
then protect `main` immediately, before accepting any change. Before adding any
deployment credential or starting a deployment, also create the `production`
environment, require a reviewer, prevent self-review, restrict it to the
protected default branch, and store credentials only on that environment.
Complete staging, production, and rollback verification as the remaining
operational release gates. Keep the GitLab repository and verified bundle
restricted until the retention and rollback policy has been approved.

This publication sequence completed on 2026-09-11. The verified root snapshot
was made public and `main` was immediately protected with all five CI jobs
required, strict status checks, linear history, administrator enforcement,
conversation resolution, and force-push and deletion disabled.
