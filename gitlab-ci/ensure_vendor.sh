#!/bin/bash
#Failne pokud failne 1 z prikazu:
set -e
gitlab-ci/ensure_composer.sh
php -d memory_limit=-1 composer.phar install --no-scripts --ignore-platform-reqs --no-progress --prefer-dist
