#!/bin/bash
cd ~/app

# Backup current .env
cp .env .env.backup

# Update DB_HOST to use TCP instead of socket
sed -i 's/^DB_HOST=.*$/DB_HOST=127.0.0.1/' .env

# Update DB_PORT to ensure it's set
sed -i 's/^DB_PORT=.*$/DB_PORT=3306/' .env

# Verify changes
echo "Configuration updated:"
grep 'DB_HOST\|DB_PORT' .env

# Clear Laravel cache
php artisan config:clear
php artisan config:cache

echo "Done!"
