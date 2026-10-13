#!/bin/sh

# Forcefully remove mpm_event configuration files to prevent AH00534 conflict
rm -f /etc/apache2/mods-enabled/mpm_event.load
rm -f /etc/apache2/mods-enabled/mpm_event.conf
ln -s /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/ 2>/dev/null || true
ln -s /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/ 2>/dev/null || true

# Run migrations and create storage symlink
php artisan migrate --force
php artisan storage:link --force

# Start Apache in the foreground using port 8080
exec apache2-foreground