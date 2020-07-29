#!/bin/bash
set -e

echo "CHECK IF CLI IS ENABLED..."
if [ "$CLI_ENABLED" = 'true' ]; then
    echo "CLI IS ENABLED"
fi

exec "$@"