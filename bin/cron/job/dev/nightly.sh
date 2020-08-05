#!/bin/sh

# Pimcore Maintenance (Full)
${HOME}/html/bin/console pimcore:maintenance

# Ecommerce Bootstrap once a day
${HOME}/html/bin/console ecommerce:indexservice:bootstrap --update-index

# Cleanup datalogger entries after 14 days
${HOME}/html/bin/console elementsdatalogger:remove 0.038

# Cleanup emails older than 14 days
 ${HOME}/html/bin/console pimcore:email:cleanup --older-than-days=14

# Cleanup all user generated content, regardless whether product requests, or applications
${HOME}/html/bin/console elements:backenditerator:pimcore:delete --pimcoreType=asset --pimcorePath=/System/UserContent/ --ignore-lock -vv --modificationDateLowerThanEqualsDays=14

# Cleanup backend iterator reports older than 7 days
 ${HOME}/html/bin/console elements:backenditerator:pimcore:delete --pimcoreType=dataObject --ignore-lock -vv --dataObjectType=backendReport --modificationDateLowerThanEqualsDays=7

# Cleanup event queue based on lifetime
${HOME}/html/bin/console pimcore:eventQueue:cleanup

# Cleanup log directory after 30 days
find ${HOME}/html/var/logs/ -type f -mtime +30 -exec rm {} \;