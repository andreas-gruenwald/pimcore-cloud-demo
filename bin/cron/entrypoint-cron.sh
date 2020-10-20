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

    echo "Also install SSH..."
    # TODO - how to pack the ssh - key of the bastion?
    mkdir /root/.ssh
    touch /root/.ssh/authorized_keys
    echo "sh-rsa AAAAB3NzaC1yc2EAAAADAQABAAACAQDUUBVn9snZ7pr5FeU5tQxod4tpCvxGT73nGk2Tn0nFKxwo7NvlriiM9GrsYf9Mz4jhc840Ro7yb9IhaXctrExg8GaBj/5dpVW092QfCaEdzP23IzBsK1i+GHf1m1oHsQOmEZa4N7750p8rjeKcXu9MkgCWeR9/e41TtPr4+EFlm032ypZnQUZ0GZaVBgj3ViQhpWA6YgGVdGEXfsBPTrOOrD6gwbMK4Sz6mYX/qV0lUug0zephGt/CCd7DqzpyPshwvo8tV6+pOZlDMc5p4nr+Q9q9c5/ZYmd5aNh8/PT6SKSttq+JWlDjSImbkjJ6t3r+9HhQ9mMMpEovrDL+n3wltwUkQ6kSznfgosqAk8IIHSA0S62rEpW8h73FsPkR4Zh4VzJFlo4Tz62O9Liv9zzOXGl8dpbspQCOhw9tyGZKG6ZRMWC/4pHDXOJ5V3NmHII8+K01y71YcPaL6gvDjw2Em29mmbjrnWYkRUkDcI+dOf59fQmi4uAdlgyXZ7MmRhSr6yJmEMsPetkb7SoHsTZBJbnq22lCivW9hjw4htda2Yq1O6gtDN7DMrqf0sCAE2i6pQ2VnqLs32hKFQyEJOSooqRvnH8lqhXikwrPTu+3frobcX4sDY6aTYwMOJRiUzDVp5oOTNt/QrUDMYVxIOS6pzcpWwFoSZ3CKc3dbxresw== ssm-user" \
        >> /root/.ssh/authorized_keys

    echo "Also install SSH..."
    apt-get install -y openssh-server
#    mkdir /var/run/sshd
    echo 'root:elements' | chpasswd
    sed -i 's/#*PermitRootLogin prohibit-password/PermitRootLogin yes/g' /etc/ssh/sshd_config
#    # SSH login fix. Otherwise user is kicked off after login
    #sed -i 's@session\s*required\s*pam_loginuid.so@session optional pam_loginuid.so@g' /etc/pam.d/sshd
#    NOTVISIBLE "in users profile"
    echo "export VISIBLE=now" >> /etc/profile

    sed -i 's/#AuthorizedKeysFile/AuthorizedKeysFile/g' /etc/ssh/sshd_config
    sed -i 's/#PubkeyAuthentication/PubkeyAuthentication /g' /etc/ssh/sshd_config
    #echo 'RSAAuthentication yes' >> etc/ssh/sshd_config

    # Test to make .env-variables accessible in AWS cloud when utilizing SSH
    env > /var/www/.initial-docker-env
    if grep -Fxq "###EXPORTS###" /var/www/.initial-docker-env
    then
        sed -e 's/=/="/' -e 's/^/export /' -e 's/$/"/' /var/www/.initial-docker-env > /var/www/.initial-docker-env-exports
        cat /var/www/.initial-docker-env-exports >> /var/www/.bashrc
        sed 's/###EXPORTS###//' /var/www/.bashrc > /var/www/.bashrc
    fi

    #/usr/sbin/sshd -D

    service ssh start
fi