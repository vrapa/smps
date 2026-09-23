# Webglobe capability checklist

This is the sanitized hosting capability record required before production can
move from the transitional GitLab job to GitHub CI with a reviewed local SFTP
handoff. Private hostnames,
accounts, credentials, database details, and absolute hosting paths belong only
in the owner's protected operational record.

## Verified hosting capabilities

Checked on 2026-09-17 and refreshed on 2026-09-22:

- The production HTTPS site and login page respond normally.
- The emergency project-root guard blocks direct access to configuration,
  dependencies, metadata, logs, and Git paths with HTTP 403.
- The current legacy deployment endpoint accepts plain FTP on port 21.
- The standard SSH port 22 is not available on that endpoint.
- Webglobe's documented SFTP/SCP/SSHFS port 222 is reachable on the same host.
  The existing account authenticates noninteractively with its current password
  and can enter the expected application target over SFTP.
- The same account completes SSH authentication, but the server explicitly
  disables command execution.
- Read-only directory navigation showed that the legacy account can leave the
  application target and reach the wider hosting tree. It is not sufficiently
  scoped for unattended GitHub deployment.
- The production service is managed Webglobe Webhosting Plus. Its control panel
  can create a separate FTP/SFTP account rooted at a selected application
  directory with granular file and directory permissions.
- An owner-approved dedicated account was created on 2026-09-22. The control
  panel confirms its application-root mapping and all required read, write,
  delete, listing, directory-change, directory-create, and rename permissions.
  Its password remains with the owner and is stored only as a protected GitHub
  environment secret, not in this repository.
- An external password-authenticated read-only SFTP probe of that account opened
  at `/`. After `cd ..`, it remained at `/`, confirming that the account is
  chrooted to the application directory. Its deployment target is therefore `.`.
  No remote file was changed.
- Public-key authentication was tested with dedicated ED25519 and RSA keys. The
  `mod_sftp` endpoint found each installed public key but rejected the signed
  login, including modern and legacy RSA signatures. Temporary private keys were
  destroyed locally, and the test `authorized_keys` plus its `.ssh` directory were
  removed from the account. They are not stored in GitHub or the repository. The
  approved fallback is the dedicated account's password in the protected
  environment.
- The production subdomain currently maps to the application root. Its editable
  directory mapping can be changed to the application's `www` directory; no
  setting was changed during inspection.
- The web runtime is PHP 8.1.34 through FPM/FastCGI with `pdo_mysql`, `intl`,
  `mbstring`, `fileinfo`, and OPcache enabled. The observed memory, execution,
  upload, and post limits are compatible with the current application. The
  application explicitly sets its own timezone.
- Temporary browser WebSSH is available for one hour after two-factor
  authentication. One temporary session was activated for the host-key check.
  Permanent console access is a separate paid option and was not activated.
- Daily provider-managed FTP snapshots and database backups are available, with
  archive preparation and restore controls. No backup or restore was started.
- The server offered the same ED25519 host key to the development workstation and
  to a scan originating inside the authenticated Webglobe WebSSH environment.
  This independent provider-side path corroborates the fingerprint. Its value is
  intentionally omitted from this public record; the exact key still has to be
  stored in the protected GitHub environment.
- The dedicated account's control-panel settings permit all countries and all IP
  addresses. Nevertheless, the first GitHub-hosted runner read-only probe timed
  out while opening TCP port 222, before authentication or any remote command.
  A second runner probe on 2026-09-23 failed at the same stage while a simultaneous
  workstation TCP probe succeeded. This is a hosting/network-path constraint,
  not an account-level GeoIP rule. The workstation is the selected transfer origin.
- Port 990 is not available on that endpoint.
- Authenticated, certificate-verified explicit and implicit FTPS probes did not
  succeed.
- The initial probes listed or checked capabilities only. The later approved
  public-key test temporarily uploaded and then removed only its `.ssh` test data;
  it did not modify application files, runtime data, or production settings.

The prepared direct GitHub SFTP workflow is transport-compatible through port 222
but cannot reach the endpoint from the tested hosted runners. The implemented
local PowerShell handoff downloads and re-audits the exact successful GitHub CI
artifact, verifies the locally pinned corroborated host key, and lets OpenSSH
prompt for the password. Plain FTP is not an acceptable fallback
because it does not protect credentials or transferred application code. Webglobe
documents SFTP/SCP/SSHFS on port 222 in its official
[encrypted transfer instructions](https://www.webglobe.cz/poradna/sifrovane-ftp-tls).

## Hosting action required

Completed setup:

- The dedicated account password is stored only in the GitHub `production`
  environment.
- The independently corroborated SSH host key is pinned in that environment.
- The reviewed local command, ignored non-password configuration template, and
  CI syntax/safety test are implemented. Live artifact preparation and read-only
  preflight wait until the implementation is merged to trusted `main`.

Remaining outcome:

1. Verify password-authenticated SFTP upload against an isolated directory before
   production handover.
2. Either enable a separate command-capable SSH account or document and rehearse
   the manual WebSSH/control-panel procedure for maintenance, cache handling, and
   rollback. Do not assume the current SFTP account can execute commands.
3. During the approved maintenance window, switch the production subdomain to
   the application `www` directory and immediately verify rewrites and the full
   non-public access boundary.

If permanent SSH/SFTP is unavailable, obtain a separate FTPS endpoint that
supports verified TLS and a documented noninteractive activation procedure.
The workflow must then be deliberately redesigned and tested for FTPS; do not
silently point the SFTP workflow at the legacy FTP service.

## Still to verify privately

- Verify the local command's read-only preflight from merged trusted `main`. The
  alternative deployment origin is selected; provider investigation of the two
  hosted-runner timeouts is optional rather than a production blocker.
- Verify write/create/rename/delete behavior in an isolated directory.
- CLI PHP version and extensions, filesystem permissions, disk headroom, and
  database version.
- Cache/maintenance commands and the exact backup download, restore, and rollback
  procedure. Rehearse rather than relying on backup availability alone.
- Whether a small isolated staging directory can be provisioned temporarily.

GitHub has a `production` environment restricted to protected branches. The
owner-approved single-maintainer model uses manual workflow dispatch as the
approval action. `SFTP_PORT=222` and the verified `SFTP_REMOTE_PATH=.` are
configured as environment variables. The four environment-scoped connection
secrets are also configured; only their names and update times were read back.
They remain temporarily during the handoff and must be removed together with the
direct-transfer workflows only after the first accepted local deployment.
