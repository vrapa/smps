# Public release security audit

This record documents the repository checks performed from 2026-08-17 through
2026-09-11 while preparing SMPS for a public release. It contains no secret
values.

## Scope and method

- Inspected the current tracked working tree and all locally reachable Git
  refs.
- Searched for common private-key headers and high-confidence token formats
  used by GitHub, GitLab, AWS, Google, Slack, and Stripe.
- Searched for credential assignments, credentials embedded in URLs, and FTP
  commands using literal credentials.
- Reviewed sensitive configuration filenames present in Git history and
  verified the repository ignore rules.
- Inventoried current and historical choir images, Git identities, names, and
  email addresses that may require permission before publication.
- Inventoried tracked filenames and file signatures for PDF and office
  documents, database exports, common score and sheet-music formats, MIDI and
  audio recordings, photographs, and archives.

The initial manual patterns were supplemented with checksum-verified Gitleaks
8.30.1 scans. The repeat recorded at `853819d` inspected 158 publishable
working-tree files and all 75 commits then reachable from local refs. Gitleaks
processed approximately 11.19 MB of Git history with fully redacted output and
reported no leaks. Ignored local configuration and dependencies were
deliberately excluded from the publishable-tree scan. The GitHub Actions
workflow repeats the history scan before an artifact can be built. The release
gate still requires one final scan of the exact history selected for
publication.

The pre-release repeat at `de55ba6` on 2026-08-27 used the same Gitleaks 8.30.1
release as CI. The official Windows x64 archive had the published SHA-256
`d29144deff3a68aa93ced33dddf84b7fdc26070add4aa0f4513094c8332afc4e`.
A `git archive` of `HEAD` limited the current-tree scan to exactly 164 tracked
files and excluded ignored local configuration, dependencies, and runtime
data. The history scan covered all 91 commits reachable from local refs and
processed approximately 11.27 MB. Both scans used fully redacted output and
reported no leaks. This is a clean pre-release result, not the final release
gate: both scans must run once more against the definitive one-commit public
snapshot immediately before it is pushed.

The private GitHub staging candidate was checked again on 2026-09-11. Its 165
tracked files had the same Git tree hash as the selected private release
commit, while its public history contained exactly one root commit authored
with the approved GitHub noreply identity. Checksum-verified Gitleaks 8.30.1
reported no leak in the snapshot tree, its one-commit history, or the complete
locally reachable private history. GitHub CI independently repeated its secret
scan successfully before building the release artifact. The checks are
repeated whenever the root snapshot is replaced before publication.

A post-publication repeat on 2026-09-11 inspected all 165 files on public
`main`. Filename and binary-signature checks found no PDF, Office/ZIP, database
export, score or sheet-music format, MIDI, audio recording, JPEG photograph,
or archive. The only tracked raster images are the application favicon and six
jQuery UI icon sprites; the other binary assets are the two Bootstrap Icons
font files. The public history contains only the approved GitHub noreply commit
identity. A targeted review found only reserved `.test` email addresses in
tests and the approved noreply address outside upstream Composer metadata.

The current-state repeat on 2026-09-16 inspected public `main` at
`fd17c4e1005a2a0e921549f51f4c9f0f9e16dc87`. The automated public-content
policy passed against the index and every commit reachable from the local ref
set, which is a superset of the seven branches then reported by the GitHub API.
The successful default-branch CI run `35084140291` repeated the full-history
Gitleaks and path-policy scans. Its downloaded deployment artifact passed the
archive policy; its SHA-256 was
`ae3bbf0761fafd74b0c56058e9a5f4b9f89f972d9ce471435833ee74ee8627f2`.

GitHub reported no tags, releases, release assets, or production-deployment
runs. A count-only review of 15 public issue/pull-request bodies and all public
issue comments found no GitHub user attachments, linked image/document/media
files, credential-bearing URLs, private-key headers, or IP literals. A
count-only scan of the current `main` CI log found no credential URLs,
private-key headers, FTP/SFTP URLs, production-domain references, user
attachments, or non-loopback IPv4 literals. Git history reports only the
approved GitHub noreply author identity.

This is a clean current-state audit, not the final production release gate.
Older unexpired Actions artifacts were inventoried by metadata but not all
downloaded individually; they were built from the already reviewed public
history and expire automatically. Re-run the complete audit against the exact
production SHA and its retained artifact after the remaining hosting work, and
inspect any deployment log created at that time.

## Findings and remediation

- No high-confidence private keys, access tokens, or embedded URL credentials
  were found in the current tracked tree.
- Removed a hard-coded remote IP that enabled Tracy debug mode and removed an
  obsolete commented debug example. Debug mode now fails closed and can only be
  enabled explicitly through `SMPS_DEBUG=1` in a trusted local environment.
- Removed `.gitlab-ci.yml.old`, which contained obsolete deployment commands
  and commented local test credentials.
- Removed `phpstan-baseline.neon`, a large baseline for an unrelated `Erudio`
  project, and removed its include from the active PHPStan configuration. Also
  removed the obsolete `phpstan-basic.neon` configuration and its batch
  wrapper, which targeted the same unrelated source tree.
- Verified that `config/local.neon`, `config/phinx.php`, legacy
  `config/phinx.yaml`, `www/dokumenty`, `log`, `temp`, and `vendor` are ignored.
  Only example configuration files and placeholder `.gitignore` files are
  tracked in protected runtime paths.
- Removed EXIF camera, capture-time, MakerNote, and embedded-thumbnail metadata
  from two choir photographs during the private-tree audit. The three choir
  photographs were subsequently removed from version control altogether;
  `www/images/carousel` is now ignored and protected from deployment artifacts.
- Verified after publication that neither `www/dokumenty` nor
  `www/images/carousel` has any tracked file and that the repository contains
  no choir photographs, sheet music, scores, PDFs, office documents, MIDI or
  audio files, or database exports.
- Removed the obsolete commented footer block that contained the maintainer's
  personal name and was never rendered by the application.

The existing private Git history still contains files and settings removed by
the cleanup. It must not be published unchanged. The release gate requires a
clean public history or a reviewed history rewrite after a verified backup.
`docs/public-history.md` defines the preferred clean-snapshot procedure, which
preserves the original refs in a restricted verified bundle and avoids a
force-push or publication of old objects.

The versioned PHPStan level 0 configuration passes across application, test,
CLI, migration, and web-entry PHP. Raising the analysis level remains future
code-quality work; existing higher-level findings are not hidden behind a
borrowed baseline.

## Personal-data inventory requiring a decision

- Git history contains one author/committer identity with the maintainer's name
  and private email address. That history will remain restricted and will not
  be copied to GitHub.
- The existing private history retains the removed commented footer name in an
  older blob. The selected clean-snapshot strategy excludes it.
- The current tree contains no choir photographs. The existing private history
  retains three photographs under `www/images/carousel/`, so it must remain
  private; the clean public snapshot deliberately excludes those historical
  objects.

The current tracked tree contains no first-party private email address. Email
addresses in `composer.lock` are upstream dependency-author metadata. The
maintainer's name is intentionally public in the MIT copyright notice, and the
approved public root commit will use author `Radovan Kraus` with the GitHub
address `28861508+vrapa@users.noreply.github.com`. The clean snapshot therefore
avoids publishing the private Git identity, historical footer blob, and
photographs.
