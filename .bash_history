git config --global --add safe.directory /var/www
composer install --no-dev --optimize-autoloader --no-interaction
ls -la vendor/
exit
