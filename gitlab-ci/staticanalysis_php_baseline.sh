#!/bin/bash
#Failne pokud failne 1 z prikazu:
set -e
gitlab-ci/config_files.sh
gitlab-ci/ensure_vendor.sh
php -d memory_limit=-1 bin/console cache:clear --env=dev
php -d memory_limit=-1 vendor/bin/phpstan analyse --configuration phpstan.neon --generate-baseline
#TODO: přidat tests - momentálně nemůže najít třídy PHPUnit :-(
