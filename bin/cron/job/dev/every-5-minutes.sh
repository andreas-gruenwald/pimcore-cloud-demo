#!/bin/sh

# Pimcore - Maintenance
~/www/bin/console pimcore:maintenance -j scheduledtasks,runSanityCheck

# Process Manager Maintenance
#~/www/bin/console process-manager:maintenance

# Ecommerce Queue Processing
~/www/bin/console ecommerce:indexservice:process-queue preparation
~/www/bin/console ecommerce:indexservice:process-queue update-index