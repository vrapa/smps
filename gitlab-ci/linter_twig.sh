#!/bin/bash
#Failne pokud failne 1 z prikazu:
set -e
gitlab-ci/config_files.sh
gitlab-ci/ensure_vendor.sh
# --env=prod zajisti, ze to bude hlasit neexistujici dump funkci pri jejim volani => to chces
php -d memory_limit=-1 bin/console lint:twig templates/ --env=prod