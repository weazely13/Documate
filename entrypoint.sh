#!/bin/sh

# Forcefully remove mpm_event configuration files right at boot to prevent AH00534 conflict
rm -f /etc/apache2/mods-enabled/mpm_event.load
rm -f /etc/apache2/mods-enabled/mpm_event.conf

# Ensure mpm_prefork is active
ln -s /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/ 2>/dev/null || true
ln -s /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/ 2>/dev/null || true

# Run Laravel migrations only once on boot
php artisan migrate --force

# Start Apache in the foreground using port 8080
exec apache2-foreground