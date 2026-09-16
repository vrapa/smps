# Legacy GitLab handover

This document records only the sanitized operational state needed to prevent the
legacy GitLab project and GitHub from deploying concurrently. It deliberately
omits the private GitLab address, project identifiers, private commit identifiers,
runner details, credential values, and hosting connection details.

## Read-only inventory

The inventory was repeated on 2026-09-16 through the authenticated GitLab API.
No setting, pipeline, variable, credential, branch, or repository state was
changed.

- The legacy project is internal, unarchived, and uses a protected `master`
  default branch. CI jobs remain enabled.
- No pipeline was running or pending. The most recent pipeline was from
  2025-10-10; it stopped at the quality stage and both deployment jobs were
  skipped.
- There are no pipeline schedules, project hooks, deploy keys, or deployment
  environments.
- Two active runners are visible to the project.
- Three legacy FTP variables remain. Their names are already referenced by the
  public transitional `.gitlab-ci.yml`; their values were not printed, copied,
  or recorded. Their current protection/masking metadata does not meet the
  target deployment policy.
- The committed transitional pipeline still defines two automatic deployment
  jobs for `master`. Both use unencrypted FTP, and one performs a deleting mirror
  of dependencies. A future successful default-branch pipeline could therefore
  still update production.

This inventory is not a freeze. The absence of a current pipeline does not make
the old deployment path inactive.

## Controlled freeze and retirement

Immediately before the first GitHub production deployment:

1. Confirm no GitLab pipeline or deployment job is running or pending.
2. Disable project CI jobs or otherwise prevent all pipeline creation before
   approving the GitHub production job. Do not rely only on branch discipline.
3. Keep the legacy FTP variables only for the shortest recovery interval agreed
   by the owner, without using the old pipeline concurrently with GitHub.
4. Record the sanitized freeze time and mechanism in the production runbook.

After the GitHub deployment and recovery path have been accepted:

1. Revoke the legacy FTP credential and remove the three project variables.
2. Disable or detach legacy project runners if they are dedicated to this
   project; do not affect shared runners or unrelated projects.
3. Archive the GitLab project read-only after refreshing the confidential backup.
4. Remove `.gitlab-ci.yml` and `gitlab-ci/` from active GitHub source in a final,
   separately reviewed cleanup commit.

Disabling GitLab CI, changing variables or credentials, modifying runners, and
archiving the project are operational mutations. They require explicit owner
authorization and are intentionally not performed by this documentation step.
