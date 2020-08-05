#!/bin/bash
set -e

#
# Added from docker-tools. This file is added to /usr/local/bin/entrypoint
#

echo "CHECK IF CLI IS ENABLED..."
if [ "$CLI_ENABLED" = 'true' ]; then
    INPUT_ENV="dev"
    echo "CLI_ENABLED is true, installing crontab..."

    # for logging cron on docker, see https://github.com/moby/moby/issues/19616
    mkdir /var/www/html/var/logs
    touch /var/www/html/var/logs/cron.log
    chown -R www-data:www-data /var/www/html/var/logs
    ln -sf /proc/1/fd/1 /var/www/html/var/logs/cron.log

    # let www-data user execute sudo without password, so that logs
    # can be sent correctly.
    echo "www-data ALL=(ALL) NOPASSWD: ALL" >> /etc/sudoers

    # make environment variables available for crontab
    printenv | grep -e "APP_ENV\|s3\|MYSQL\|REDIS" >> /etc/environment

    service cron start
    #env > /var/www/html/.env
    #chown www-data:www-data /var/www/html/.env
    sed "s/{env}/$INPUT_ENV/g" /var/www/html/bin/cron/crontab_definition | crontab -u www-data -
    echo "\n\n*************************\n crontab installed (${INPUT_ENV})! \n*************************\n";
    crontab -u www-data -l
    echo "\n";
fi