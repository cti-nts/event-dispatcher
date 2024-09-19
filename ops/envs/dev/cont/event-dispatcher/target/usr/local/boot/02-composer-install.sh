#!/bin/bash

PARENT_PATH=$(
  cd "$(dirname "${BASH_SOURCE[0]}")"
  pwd -P
)

if [[ $SKIP_COMPOSER_INSTALL -eq 1 ]]; then
  echo "SKIP_COMPOSER_INSTALL is true. Skipping composer install."
  return 0
fi

runuser -l hostuser -c "composer install"
