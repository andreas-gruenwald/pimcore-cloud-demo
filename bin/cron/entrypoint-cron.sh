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
    echo "first, logs are setup..."
    mkdir /var/www/html/var/logs
    touch /var/www/html/var/logs/cron.log
    chown -R www-data:www-data /var/www/html/var/logs
    echo "second, a symbolic link is set..."
    ln -sf /proc/1/fd/1 /var/www/html/var/logs/cron.log

    echo "third, permissions are adapted..."
    # let www-data user execute sudo without password, so that logs
    # can be sent correctly.
    echo "www-data ALL=(ALL) NOPASSWD: ALL" >> /etc/sudoers

    echo "fourth, the /etc/environment is skpped, because AWS cannot deal with it..."
    # make environment variables available for crontab
    #printenv | grep -e "APP_ENV\|s3\|MYSQL\|REDIS" >> /etc/environment

    echo "fifth, cron is started as a service..."
    service cron start
    #env > /var/www/html/.env
    #chown www-data:www-data /var/www/html/.env

    echo "sixth, crontab is installed..."
    sed "s/{env}/$INPUT_ENV/g" /var/www/html/bin/cron/crontab_definition | crontab -u www-data -
    echo "\n\n*************************\n crontab installed (${INPUT_ENV})! \n*************************\n";
    crontab -u www-data -l
    echo "\n";
    echo "finally, the entrypoint-cron.sh script is exited..."
fi