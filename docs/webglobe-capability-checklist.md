# Webglobe capability checklist

This is the sanitized hosting capability record required before production can
move from the transitional GitLab job to GitHub Actions. Private hostnames,
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
  Its password remains with the owner and is not stored in GitHub or this repository.
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
- Port 990 is not available on that endpoint.
- Authenticated, certificate-verified explicit and implicit FTPS probes did not
  succeed.
- The initial probes listed or checked capabilities only. The later approved
  public-key test temporarily uploaded and then removed only its `.ssh` test data;
  it did not modify application files, runtime data, or production settings.

The prepared GitHub SFTP workflow is transport-compatible through port 222 and
uses password authentication without placing the password on the command line.
It still requires protected environment secrets, the pinned corroborated host
key, and a runner-originated rehearsal. Plain FTP is not an acceptable fallback
because it does not protect credentials or transferred application code. Webglobe
documents SFTP/SCP/SSHFS on port 222 in its official
[encrypted transfer instructions](https://www.webglobe.cz/poradna/sifrovane-ftp-tls).

## Hosting action required

Preferred outcome:

1. Store the dedicated account password only in the GitHub `production`
   environment.
2. Pin the independently corroborated SSH host key in that environment.
3. Verify password-authenticated SFTP upload against an isolated directory before
   production handover.
4. Either enable a separate command-capable SSH account or document and rehearse
   the manual WebSSH/control-panel procedure for maintenance, cache handling, and
   rollback. Do not assume the current SFTP account can execute commands.
5. During the approved maintenance window, switch the production subdomain to
   the application `www` directory and immediately verify rewrites and the full
   non-public access boundary.

If permanent SSH/SFTP is unavailable, obtain a separate FTPS endpoint that
supports verified TLS and a documented noninteractive activation procedure.
The workflow must then be deliberately redesigned and tested for FTPS; do not
silently point the SFTP workflow at the legacy FTP service.

## Still to verify privately

- Store the dedicated password and independently corroborated host key in the
  protected environment, then verify authentication from the GitHub runner
  network.
- Verify write/create/rename/delete behavior in an isolated directory.
- CLI PHP version and extensions, filesystem permissions, disk headroom, and
  database version.
- Cache/maintenance commands and the exact backup download, restore, and rollback
  procedure. Rehearse rather than relying on backup availability alone.
- Whether a small isolated staging directory can be provisioned temporarily.

GitHub has a `production` environment restricted to protected branches. The
owner-approved single-maintainer model uses manual workflow dispatch as the
approval action. `SFTP_PORT=222` and the verified `SFTP_REMOTE_PATH=.` are
configured as environment variables and were read back successfully; no
credentials or host identity are stored there yet.
