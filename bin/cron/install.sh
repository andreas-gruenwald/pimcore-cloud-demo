#!/bin/sh
#
# Installation script for crontab. Can be repeated anytime.
# Prerequisits:
#   - ~/www/bin/cron/crontab_definition must exist.
#   - ~/www/bin/cron/job/* must contain the executable shell scripts
#
#

DIRS="$(ls ~/www/bin/cron/job/ | tr '\n' ',')"

### TODO - activate here...

read -p "Which environment do you want to install ($DIRS... see bin/cron/job): " INPUT_ENV
if [ -z "$INPUT_ENV" ]
then
    error_exit()
    {
        echo "$1" 1>&2
        exit 1
    }
    error_exit "You must enter a value, such as \"dev\" or \"live\")."
fi

# replace:
##(cat ~/www/bin/cron/crontab_definition) | crontab -
sed "s/{env}/$INPUT_ENV/g" ~/www/bin/cron/crontab_definition | crontab -
echo "\n\n*************************\n crontab installed (${INPUT_ENV})! \n*************************\n";
crontab -l
echo "\n";
