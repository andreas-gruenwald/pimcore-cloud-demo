#!/bin/bash
set -e

#
# Added from docker-tools. This file is added to /usr/local/bin/entrypoint
#

echo "CHECK IF CLI IS ENABLED..."
echo "::::${CLI_ENABLED}".
if [ "$CLI_ENABLED" = 'true' ]; then
    INPUT_ENV="dev"
    echo "CLI_ENABLED is true, installing crontab..."
    sed "s/{env}/$INPUT_ENV/g" /var/www/html/bin/cron/crontab_definition | crontab -
    echo "\n\n*************************\n crontab installed (${INPUT_ENV})! \n*************************\n";
    crontab -l
    echo "\n";
    service cron start
fi

#exec "$@"