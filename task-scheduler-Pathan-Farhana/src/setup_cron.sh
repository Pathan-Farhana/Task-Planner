#!/bin/bash

# Path to PHP (may vary depending on system)
PHP_PATH=$(which php)

# Path to cron.php
CRON_FILE="$(dirname "$(realpath "$0")")/cron.php"

# Cron expression: every hour at minute 0
CRON_JOB="0 * * * * $PHP_PATH $CRON_FILE"

# Check if job already exists
(crontab -l 2>/dev/null | grep -F "$CRON_FILE") > /dev/null
if [ $? -eq 0 ]; then
    echo "CRON job already exists."
else
    # Append new cron job
    (crontab -l 2>/dev/null; echo "$CRON_JOB") | crontab -
    echo "CRON job added to run every hour."
fi
