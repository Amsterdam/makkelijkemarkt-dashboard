#!/usr/bin/env bash
echo Starting server

set -u
set -e

cat > /app/.env <<EOF
APP_ENV=prod
APP_SECRET=${MM_DASHBOARD__SECRET}
MARKT_API=${MM_DASHBOARD__API_URL}
MM_APP_KEY=${MM_DASHBOARD__APP_KEY}
EOF

php composer.phar install

cd /app/
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
chown -R www-data:www-data /app/var/cache && find /app/var/cache -type d -exec chmod -R 0770 {} \; && find /app/var/cache -type f -exec chmod -R 0660 {} \;

# Make sure log files exist, so tail won't return a non-zero exitcode
touch /app/var/log/dev.log
touch /app/var/log/prod.log
touch /var/log/nginx/access.log
touch /var/log/nginx/error.log

tail -f /app/var/log/dev.log &
tail -f /app/var/log/prod.log &
tail -f /var/log/nginx/access.log &
tail -f /var/log/nginx/error.log &

chgrp www-data /app/var/log/*.log
chmod 775 /app/var/log/*.log

nginx

chgrp -R www-data /var/lib/nginx
chmod -R 775 /var/lib/nginx/tmp

php-fpm -F
