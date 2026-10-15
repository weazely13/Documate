#!/bin/sh

# Forcefully remove mpm_event configuration files to prevent AH00534 conflict
rm -f /etc/apache2/mods-enabled/mpm_event.load
rm -f /etc/apache2/mods-enabled/mpm_event.conf
ln -s /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/ 2>/dev/null || true
ln -s /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/ 2>/dev/null || true

# Run migrations and create storage symlink
php artisan migrate --force
mkdir -p /var/www/html/storage/app/public/templates \
         /var/www/html/storage/app/public/generated-documents
chown -R www-data:www-data /var/www/html/storage/app
php artisan storage:link --force

runuser -u www-data -- sh -c 'while true; do php artisan queue:work --sleep=3 --tries=3 --timeout=170 --max-time=3600; sleep 2; done' >> /var/www/html/storage/logs/queue.log 2>&1 &
runuser -u www-data -- sh -c 'while true; do php artisan schedule:run >/dev/null 2>&1; sleep 60; done' &

# Start Apache in the foreground using port 8080
exec apache2-foreground