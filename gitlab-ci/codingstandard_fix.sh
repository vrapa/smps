#!/bin/bash
#Failne pokud failne 1 z prikazu:
set -e
gitlab-ci/ensure_vendor.sh
vendor/bin/phpcbf
git add src
git push origin $CI_BUILD_REF_NAME
