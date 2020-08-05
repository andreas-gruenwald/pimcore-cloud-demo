#!/bin/sh

# Pimcore - Maintenance
${HOME}/html/bin/console pimcore:maintenance -j scheduledtasks,runSanityCheck

# Process Manager Maintenance
#~/www/bin/console process-manager:maintenance

# Ecommerce Queue Processing
${HOME}/html//bin/console ecommerce:indexservice:process-queue preparation
${HOME}/html/bin/console ecommerce:indexservice:process-queue update-index