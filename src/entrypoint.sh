#!/bin/sh

echo "
███████ ████████ ██████  ███████  █████  ███    ███ ██ ███    ██  ██████      ██████  ██      ██    ██ ███████
██         ██    ██   ██ ██      ██   ██ ████  ████ ██ ████   ██ ██           ██   ██ ██      ██    ██ ██
███████    ██    ██████  █████   ███████ ██ ████ ██ ██ ██ ██  ██ ██   ███     ██████  ██      ██    ██ ███████
     ██    ██    ██   ██ ██      ██   ██ ██  ██  ██ ██ ██  ██ ██ ██    ██     ██      ██      ██    ██      ██
███████    ██    ██   ██ ███████ ██   ██ ██      ██ ██ ██   ████  ██████      ██      ███████  ██████  ███████
"

set -e
set -e
info() {
    { set +x; } 2> /dev/null
    echo '[INFO] ' "$@"
}
warning() {
    { set +x; } 2> /dev/null
    echo '[WARNING] ' "$@"
}
fatal() {
    { set +x; } 2> /dev/null
    echo '[ERROR] ' "$@" >&2
    exit 1
}

echo ""
echo "***********************************************************"
echo " Starting Streaming-Plus Docker Container                   "
echo "***********************************************************"

#Pulisco le directory che devo ricreare
rm -rf $JP_DATA_PATH/app/sessions
rm -rf $JP_DATA_PATH/app/cache
rm -rf $JP_DATA_PATH/app/response
rm -rf $JP_DATA_PATH/app/logs
rm -rf $JP_DATA_PATH/nginx/logs
rm -rf $JP_DATA_PATH/jellyfin/log
rm -rf $JP_DATA_PATH/jellyfin/cache

#Creo le directory che mi servono
info "-- Creating the necessary folders if they do not already exist"
mkdir -p $JP_DATA_PATH/app/sessions
mkdir -p $JP_DATA_PATH/app/cache
mkdir -p $JP_DATA_PATH/app/response
mkdir -p $JP_DATA_PATH/app/logs
mkdir -p $JP_DATA_PATH/jellyfin/cache
mkdir -p $JP_DATA_PATH/jellyfin/log
mkdir -p $JP_DATA_PATH/jellyfin/config
mkdir -p $JP_DATA_PATH/nginx/logs
mkdir -p $JP_DATA_PATH/library

#Configurazione Laravel
info "-- Configuring the basic dependencies of the app"
if [ ! -f $JP_DATA_PATH/app/database.sqlite ]; then
  cp /var/www/database/database.sqlite $JP_DATA_PATH/app/database.sqlite
fi
composer install --no-interaction --prefer-dist --optimize-autoloader 2> /dev/null
php artisan migrate 2>&1 > /dev/null
php artisan queue:clear 2>&1 > /dev/null

#Customizzazioni Jellyfin
info "-- Performing customizations to Jellyfin"
if [ -d /usr/share/jellyfin/web ]; then
  cp -r /var/src/img/* /usr/share/jellyfin/web/assets/img
  cp -r /var/src/img/web-icons/* /usr/share/jellyfin/web/
  echo "$(cat /var/src/themes/theme.css)" >> /usr/share/jellyfin/web/themes/dark/theme.css
  echo "$(cat /var/src/themes/theme.css)" >> /usr/share/jellyfin/web/themes/light/theme.css
  cp /var/src/jellyfin/config/network.xml $JP_DATA_PATH/jellyfin/config/network.xml
  if [ ! -f $JP_DATA_PATH/jellyfin/config/branding.xml ]; then
    cp /var/src/jellyfin/config/branding.xml $JP_DATA_PATH/jellyfin/config/branding.xml
  fi
fi

#Configurazione MediaFlowProxy
API_PASSWORD=$(head /dev/urandom | tr -dc A-Za-z0-9 | head -c 32)
export API_PASSWORD="$API_PASSWORD"

#Cambio i permessi nelle cartelle data
info "-- Changing permissions to folders and files"
chown -R $USER_NAME:$GROUP_NAME $JP_DATA_PATH

echo ""
echo "***********************************************************"
echo " Starting Streaming-Plus Services                          "
echo "***********************************************************"

## Start Supervisord
supervisord -c /etc/supervisor/supervisord.conf