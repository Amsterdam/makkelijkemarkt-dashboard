#!/usr/bin/env bash
echo Starting server

set -u
set -e

cat > /app/.env <<EOF
APP_ENV=prod
APP_SECRET=${MM_DASHBOARD__SECRET}
MARKT_API=${MM_DASHBOARD__API_URL}
MMAPPKEY=MM_DASHBOARD__APP_KEY
EOF

php composer.phar install

cd /app/app
php console cache:clear --env=prod
php console cache:warmup --env=prod
chown -R www-data:www-data /app/app/cache && find /app/app/cache -type d -exec chmod -R 0770 {} \; && find /app/app/cache -type f -exec chmod -R 0660 {} \;
php console assetic:dump --env=prod || /bin/true

# Make sure log files exist, so tail won't return a non-zero exitcode
touch /app/var/log/dev.log
touch /app/var/log/prod.log
touch /var/log/nginx/access.log
touch /var/log/nginx/error.log

tail -f /app/var/log/dev.log &
tail -f /app/var/log/prod.log &
tail -f /var/log/nginx/access.log &
tail -f /var/log/nginx/error.log &

chgrp www-data /app/var/logs/*.log
chmod 775 /app/var/logs/*.log

nginx

chgrp -R www-data /var/lib/nginx
chmod -R 775 /var/lib/nginx/tmp

php-fpm -F
