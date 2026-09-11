#!/bin/bash

if [ ! -e "composer.phar" ]
then
  # neexistuje composer, zkusime nainstalovat
  gitlab-ci/install_composer.sh
fi
if [ ! -e "composer.phar" ]
then
    # ani po instalaci neexistuje, konec
    echo Error: Cannot install composer>&2
    exit 1
fi
#aktualizace composeru
php composer.phar self-update
