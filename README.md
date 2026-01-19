# LogCleaner for Magento 2

Automated log and directory cleanup module for Magento 2. This module allows you to automatically clean up specific directories within the `var` folder based on configured capacity limits or schedules.

## Features

- **Automated Cleanup**: run cleanup jobs via Magento Cron.
- **Capacity Management**: Configure a maximum capacity (e.g., 1GB) for directories. If a directory exceeds this size, it will be emptied.
- **Selective Cleanup**: Choose which subdirectories within `var/` to include in the cleanup process.
- **Configurable Frequency**: Set the cron schedule expression directly from the admin panel.

## Installation

### Composer
1. Required the module via composer:
   ```bash
   composer require aoropeza/logcleaner
   ```
2. Enable the module:
   ```bash
   bin/magento module:enable Aoropeza_LogCleaner
   ```
3. Upgrade setup:
   ```bash
   bin/magento setup:upgrade
   ```
4. Compile and deploy (if in production):
   ```bash
   bin/magento setup:di:compile
   bin/magento setup:static-content:deploy
   ```

### Manual Installation
1. Extract the extension to `app/code/Aoropeza/LogCleaner`.
2. Run the enable commands as above.

## Configuration

Navigate to **Store > Configuration > Dev Oropeza > Log Directory**.

### General
- **Enable**: Enable or disable the module functionalities.
- **Directory Capacity**: Enable to check directory size before cleaning.
- **Directory Size**: (If enabled above) set the size threshold (e.g., `1GB`, `500MB`). If a directory exceeds this size, it will be cleaned.
- **Backup Directory**: Enable to create a zip backup of the directory before deletion. backups are stored in `var/backups/`.
- **Frequency**: Cron expression for how often the check runs (e.g., `* * * * *` for every minute, `0 2 * * *` for daily at 2 AM).

### Folders to Clean
- **Folders**: Select which folders within the `var` directory should be monitored and cleaned.

## Copyright & License

Copyright © 2026 Adolfo Oropeza. All rights reserved.

This project is licensed under the MIT License - see the [LICENSE](LICENSE.md) file for details.
