# GitHub production deployment

The production workflow deploys only an artifact created by a successful `CI`
push run on the repository's default branch. It is started manually with the
numeric CI run ID, then waits for the protection rules configured on the
GitHub `production` environment.

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
3. Validate the adjusted configuration on staging, then verify that the current
   application still starts and its main read-only pages work.
4. Deploy the tested artifact and verify login, roles, users, songs, concerts,
   and uploads.

The deployment artifact deliberately excludes `config/local.neon`, so the
workflow cannot perform this cleanup and must never replace that protected
file.

## Installation language

The user-interface language is selected once for the whole installation by
the `parameters.locale` value. Supported values are `cs`, `en`, `de`, and
`nl`; versioned configuration defaults to `cs`. To select another language,
set the value in the protected `config/local.neon`, validate the application on
staging, and clear the Nette cache through the normal deployment procedure.

Keep production on `cs` until every UI slice and the acceptance matrix in
`docs/localization-plan.md` are complete. The other values are accepted while
the translation infrastructure is developed, but untranslated legacy text
deliberately remains Czech during the incremental rollout.

The deployment artifact excludes `config/local.neon` and therefore cannot
change the production language by itself. A production locale change is a
reviewed protected-configuration operation, not a database migration.

The public name shown in the navigation and browser title is configured by
`parameters.applicationName`. It defaults to `SMPS Bruntál`; another choir can
override it in `config/local.neon` without changing a template. The value is
public display text, not a place for credentials or other private data.

## GitHub environment setup

Create a `production` environment after the GitHub repository is public and:

1. Require at least one reviewer and prevent self-review.
2. Restrict deployments to the protected default branch.
3. Disable administrator bypass if the repository policy permits it.
4. Store the following values on the environment, not as repository-wide
   credentials.

Environment secrets:

- `SFTP_HOST`: production SFTP hostname.
- `SFTP_USERNAME`: dedicated deployment account.
- `SFTP_PRIVATE_KEY`: private key for that account.
- `SFTP_KNOWN_HOSTS`: pinned `known_hosts` line obtained from the hosting
  provider through a trusted channel. The workflow deliberately does not use
  `ssh-keyscan` at deployment time.

Environment variables:

- `SFTP_PORT`: SFTP port; defaults to `22` when omitted.
- `SFTP_REMOTE_PATH`: application root as seen by the restricted SFTP account.

The account must be restricted to this application and must not provide access
to database data, user uploads outside the application root, or unrelated
hosting content. Test the same workflow and account restrictions against a
staging target before approving a production run.

## Deploying and rolling back

Open a successful `CI` run created by a push to the default branch and copy its
numeric run ID from the URL. Start `Deploy production`, enter that ID, review
the pending environment deployment, and approve it only after checking the
commit SHA and artifact.

To roll back application files, run the workflow again with the run ID of an
earlier successful CI artifact that is still within its retention period. This
is a file rollback only. Database migrations remain a separate, explicitly
approved CLI operation and must be assessed for compatibility before either a
deployment or rollback.

The first real deployment must target staging. Retire the GitLab deployment
only after staging and production verification, including login, roles,
uploads, protected runtime paths, and rollback.
