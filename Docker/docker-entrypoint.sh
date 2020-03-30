#!/usr/bin/env bash
echo Starting server

set -u
set -e

cat > /app/app/config/parameters.yml <<EOF
parameters:
  mailer_transport:     ${MM_DASHBOARD__MAILER__TRANSPORT}
  mailer_host:          ${MM_DASHBOARD__MAILER__HOST}
  mailer_user:          ${MM_DASHBOARD__MAILER__USER}
  mailer_password:      ${MM_DASHBOARD__MAILER__PASSWORD}
  mailer_port:          ${MM_DASHBOARD__MAILER__PORT}
  mailer_encryption:    ${MM_DASHBOARD__MAILER__ENCRYPTION}
  secret:               ${MM_DASHBOARD__SECRET}
  markt_api.url:        ${MM_DASHBOARD__API_URL}
  markt_api.guzzle.verify: true
  mm_app_key:           ${MM_DASHBOARD__APP_KEY}
  session_cookie_only_secure_connection: true
EOF

php composer.phar install

cd /app/app
php console cache:clear --env=prod
php console cache:warmup --env=prod
chown -R www-data:www-data /app/app/cache && find /app/app/cache -type d -exec chmod -R 0770 {} \; && find /app/app/cache -type f -exec chmod -R 0660 {} \;
php console assetic:dump --env=prod || /bin/true

nginx
php-fpm -F
