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

1. Provide a dedicated deployment account restricted to this application and
   install a dedicated key for noninteractive SFTP on port 222.
2. Obtain the SSH host key through a trusted provider channel and pin it in the
   GitHub `production` environment.
3. Verify key-authenticated SFTP upload against an isolated directory before
   production handover.
4. Either enable a separate command-capable SSH account or document and rehearse
   the manual WebSSH/control-panel procedure for maintenance, cache handling, and
   rollback. Do not assume the current SFTP account can execute commands.

If permanent SSH/SFTP is unavailable, obtain a separate FTPS endpoint that
supports verified TLS and a documented noninteractive activation procedure.
The workflow must then be deliberately redesigned and tested for FTPS; do not
silently point the SFTP workflow at the legacy FTP service.

## Still to verify privately

- Exact Webglobe product and whether command-capable permanent SSH requires
  activation or a hosting-plan change. SFTP itself is already available.
- Whether Webglobe can root a separate FTP/SFTP account at this application's
  directory; the public provider documentation confirms IP/GeoIP controls but
  does not document per-directory account scoping.
- Application root and `www` document-root configuration in the control panel.
- Web and CLI PHP versions, required extensions, limits, timezone, OPcache, disk
  quota, permissions, and database version.
- Cache/maintenance commands and the exact backup and rollback mechanism.
- Whether a small isolated staging directory can be provisioned temporarily.

GitHub has a `production` environment restricted to protected branches. The
owner-approved single-maintainer model uses manual workflow dispatch as the
approval action. `SFTP_PORT` and the verified target are configured as
environment variables; no credentials or host identity are stored there yet.
