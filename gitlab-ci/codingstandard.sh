#!/bin/bash
gitlab-ci/ensure_vendor.sh
vendor/bin/phpcs --cache=./.tmp/phpcs
if [ $? != 0 ] ; then
  echo -e '\e[93mTip\e[0m: Pro automatickou opravu vetsiny chyb pouzijte "\e[92mphpcbf.bat\e[0m"'
  echo -e '\e[93mTip\e[0m: Pro ziskani seznamu zbyvajicich chyb lze pouzit "\e[92mphpcs.bat\e[0m"';
  exit 1
fi
