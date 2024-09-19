#!/bin/sh

if [[ $SKIP_COMPOSER_INSTALL -eq 1 ]]; then
  echo "SKIP_COMPOSER_INSTALL is true. Skipping behat init."
  return 0
fi
if [ -f vendor/bin/behat ]; then
  runuser -l hostuser -c "vendor/bin/behat --init"
fi
