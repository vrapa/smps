#!/bin/sh
#Failne pokud failne 1 z prikazu:
set -e
gitlab-ci/config_files.sh
gitlab-ci/ensure_composer.sh
gitlab-ci/ensure_vendor.sh

php -d memory_limit=-1 bin/console cache:clear --env=dev
php bin/phpunit --no-coverage --colors=never
#--coverage-text