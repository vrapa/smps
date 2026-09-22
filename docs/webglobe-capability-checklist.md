# Webglobe capability checklist

This is the sanitized hosting capability record required before production can
move from the transitional GitLab job to GitHub Actions. Private hostnames,
accounts, credentials, database details, and absolute hosting paths belong only
in the owner's protected operational record.

## Verified without remote changes

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
  disables command execution. Key authentication has not yet been configured or
  verified.
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
- The production subdomain currently maps to the application root. Its editable
  directory mapping can be changed to the application's `www` directory; no
  setting was changed during inspection.
- The web runtime is PHP 8.1.34 through FPM/FastCGI with `pdo_mysql`, `intl`,
  `mbstring`, `fileinfo`, and OPcache enabled. The observed memory, execution,
  upload, and post limits are compatible with the current application. The
  application explicitly sets its own timezone.
- Temporary browser WebSSH is available for one hour after two-factor
  authentication. Permanent console access is a separate paid option. Neither
  option was activated.
- Daily provider-managed FTP snapshots and database backups are available, with
  archive preparation and restore controls. No backup or restore was started.
- The server offered an ED25519 host key consistently during the probes, but its
  fingerprint was learned from the connection itself and is not trusted provider
  evidence. The value is intentionally omitted from this public record.
- Port 990 is not available on that endpoint.
- Authenticated, certificate-verified explicit and implicit FTPS probes did not
  succeed.
- The probes listed or checked capabilities only. They did not upload, delete,
  rename, or modify remote data.

The prepared GitHub SFTP workflow is transport-compatible through port 222, but
password authentication and a network-observed host key are not sufficient for
the production workflow. Plain FTP is not an acceptable fallback because it does
not protect credentials or transferred application code in transit. Webglobe
documents SFTP/SCP/SSHFS on port 222 in its official
[encrypted transfer instructions](https://www.webglobe.cz/poradna/sifrovane-ftp-tls).

## Hosting action required

Preferred outcome:

1. Verify the dedicated account is effectively restricted to this application
   over SFTP, then install a dedicated key for noninteractive access on port 222.
2. Obtain the SSH host key through a trusted provider channel and pin it in the
   GitHub `production` environment.
3. Verify key-authenticated SFTP upload against an isolated directory before
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

- Authenticate the dedicated directory-rooted account over SFTP and verify its
  effective boundary and required permissions, then install and test its key.
- Obtain the trusted host key independently and verify key authentication from
  the GitHub runner network.
- CLI PHP version and extensions, filesystem permissions, disk headroom, and
  database version.
- Cache/maintenance commands and the exact backup download, restore, and rollback
  procedure. Rehearse rather than relying on backup availability alone.
- Whether a small isolated staging directory can be provisioned temporarily.

GitHub has a `production` environment restricted to protected branches. The
owner-approved single-maintainer model uses manual workflow dispatch as the
approval action. `SFTP_PORT` and the verified target are configured as
environment variables; no credentials or host identity are stored there yet.
