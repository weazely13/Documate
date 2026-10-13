#!/bin/sh

# Run migrations only when container boots
php artisan migrate --force

# Start Apache in the foreground to keep container alive
exec apache2-foreground