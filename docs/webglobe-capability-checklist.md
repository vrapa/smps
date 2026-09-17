# Webglobe capability checklist

This is the sanitized hosting capability record required before production can
move from the transitional GitLab job to GitHub Actions. Private hostnames,
accounts, credentials, database details, and absolute hosting paths belong only
in the owner's protected operational record.

## Verified without remote changes

Checked on 2026-09-17:

- The production HTTPS site and login page respond normally.
- The emergency project-root guard blocks direct access to configuration,
  dependencies, metadata, logs, and Git paths with HTTP 403.
- The current legacy deployment endpoint accepts plain FTP on port 21.
- Port 22 is not available on that endpoint, so it cannot currently provide
  SSH or SFTP.
- Port 990 is not available on that endpoint.
- Authenticated, certificate-verified explicit and implicit FTPS probes did not
  succeed.
- The probes listed or checked capabilities only. They did not upload, delete,
  rename, or modify remote data.

The legacy endpoint therefore cannot be reused by the prepared GitHub SFTP
workflow. Plain FTP is not an acceptable fallback because it does not protect
credentials or transferred application code in transit.

## Hosting action required

Preferred outcome:

1. Activate permanent, noninteractive SSH/SFTP for this hosting account.
2. Provide a dedicated deployment account restricted to this application.
3. Obtain the SSH host key through a trusted provider channel and pin it in the
   GitHub `production` environment.
4. Verify SFTP upload and SSH command execution against an isolated directory
   before production handover.

If permanent SSH/SFTP is unavailable, obtain a separate FTPS endpoint that
supports verified TLS and a documented noninteractive activation procedure.
The workflow must then be deliberately redesigned and tested for FTPS; do not
silently point the SFTP workflow at the legacy FTP service.

## Still to verify privately

- Exact Webglobe product and whether permanent SSH/SFTP requires activation or
  a hosting-plan change.
- Application root and `www` document-root configuration in the control panel.
- Web and CLI PHP versions, required extensions, limits, timezone, OPcache, disk
  quota, permissions, and database version.
- Cache/maintenance commands and the exact backup and rollback mechanism.
- Whether a small isolated staging directory can be provisioned temporarily.

GitHub currently has no `production` environment. The public repository has one
direct administrator, so a required-reviewer rule with self-review disabled
would block deployment until a second trusted reviewer is added. Create the
environment only after choosing either that second reviewer or an explicitly
documented single-maintainer approval model, and add secrets only after the
secure hosting endpoint has been verified.
