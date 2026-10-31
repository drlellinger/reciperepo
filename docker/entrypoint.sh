#!/bin/sh
set -eu

: "${ADMIN_USER:?ADMIN_USER muss gesetzt sein}"
: "${ADMIN_PASSWORD:?ADMIN_PASSWORD muss gesetzt sein}"

htpasswd -Bbn "$ADMIN_USER" "$ADMIN_PASSWORD" > /etc/recipe-collection/.htpasswd
chown root:www-data /etc/recipe-collection/.htpasswd
chmod 640 /etc/recipe-collection/.htpasswd

exec "$@"