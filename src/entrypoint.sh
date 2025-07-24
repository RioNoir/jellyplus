#!/bin/sh

echo "
     ██╗███████╗██╗     ██╗  ██╗   ██╗██████╗ ██╗     ██╗   ██╗███████╗
     ██║██╔════╝██║     ██║  ╚██╗ ██╔╝██╔══██╗██║     ██║   ██║██╔════╝
     ██║█████╗  ██║     ██║   ╚████╔╝ ██████╔╝██║     ██║   ██║███████╗
██   ██║██╔══╝  ██║     ██║    ╚██╔╝  ██╔═══╝ ██║     ██║   ██║╚════██║
╚█████╔╝███████╗███████╗███████╗██║   ██║     ███████╗╚██████╔╝███████║
 ╚════╝ ╚══════╝╚══════╝╚══════╝╚═╝   ╚═╝     ╚══════╝ ╚═════╝ ╚══════╝
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

get_string_between () {
    if [ -n "$1" ]; then
      FIND=$(echo ${1//$'\n'/})
      START=${2##*( )}
      END=${3##*( )}
      START=${FIND#*"$START"}
      END=${START%"$END"*}
      echo ${END##*( )}
    fi
}
replace_text(){
  STRING="${1/${2}/${3}}"
  echo "$STRING"
}
get_ip_address(){
  IP_ADDRESS=$(ifconfig | grep 'inet ' | awk '{print $2}' | grep -v '127.0.0.1')
  echo $IP_ADDRESS
}

echo ""
echo "***********************************************************"
echo " Starting Jellyplus Docker Container                   "
echo "***********************************************************"

#Directories customizations
rm -rf $JP_DATA_PATH/app/sessions
rm -rf $JP_DATA_PATH/app/cache
rm -rf $JP_DATA_PATH/app/response
rm -rf $JP_DATA_PATH/app/logs
rm -rf $JP_DATA_PATH/nginx/logs
rm -rf $JP_DATA_PATH/jellyfin/log
rm -rf $JP_DATA_PATH/jellyfin/cache

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

#Laravel customizations
info "-- Configuring the basic dependencies of the app"
if [ ! -f $JP_DATA_PATH/app/database.sqlite ]; then
  cp /var/www/database/database.sqlite $JP_DATA_PATH/app/database.sqlite
fi
composer install --no-interaction --prefer-dist --optimize-autoloader 2> /dev/null
php artisan migrate 2>&1 > /dev/null
php artisan queue:clear 2>&1 > /dev/null

#Jellyfin customizations
info "-- Performing customizations to Jellyfin"
if [ -d /usr/share/jellyfin/web ]; then
  cp -r /var/src/img/* /usr/share/jellyfin/web/assets/img
  cp -r /var/src/img/web-icons/* /usr/share/jellyfin/web/

  #Theme customizations
  CUSTOM_THEME="\n\n $(cat /var/src/themes/theme.css)"
  THEME="$(cat /usr/share/jellyfin/web/themes/dark/theme.css)"
  if [ -n "$THEME" ]; then
    START="/*JELLYPLUS*/"
    END="/*ENDJELLYPLUS*/"
    START=${THEME#*"$START"}
    END=${START%"$END"*}
    TOREMOVE="${END##*( )}"
    if [ "$TOREMOVE" != "$THEME" ]; then
        THEME="$(replace_text "$THEME" "$(echo "$TOREMOVE")" "$(echo "$CUSTOM_THEME")")"
    else
      ADD_THEME="\n\n/*JELLYPLUS*/ $(echo "$CUSTOM_THEME") \n\n/*ENDJELLYPLUS*/"
      THEME="$THEME $(echo "$ADD_THEME")"
    fi
  fi
  echo "$THEME" > /usr/share/jellyfin/web/themes/dark/theme.css
  echo "$THEME" > /usr/share/jellyfin/web/themes/light/theme.css
  echo "$THEME" > /usr/share/jellyfin/web/themes/appletv/theme.css
  echo "$THEME" > /usr/share/jellyfin/web/themes/blueradiance/theme.css
  echo "$THEME" > /usr/share/jellyfin/web/themes/purplehaze/theme.css
  echo "$THEME" > /usr/share/jellyfin/web/themes/wmc/theme.css

  cp /var/src/jellyfin/config/network.xml $JP_DATA_PATH/jellyfin/config/network.xml
  if [ ! -f $JP_DATA_PATH/jellyfin/config/branding.xml ]; then
    cp /var/src/jellyfin/config/branding.xml $JP_DATA_PATH/jellyfin/config/branding.xml
  fi
  if [ ! -f $JP_DATA_PATH/jellyfin/config/system.xml ]; then
    cp /var/src/jellyfin/config/system.xml $JP_DATA_PATH/jellyfin/config/system.xml
  fi
fi

#MediaFlowProxy configuration
API_PASSWORD=$(head /dev/urandom | tr -dc A-Za-z0-9 | head -c 32)
export API_PASSWORD="$API_PASSWORD"

#Folder permissions
info "-- Changing permissions to folders and files"
chown -R $USER_NAME:$GROUP_NAME $JP_DATA_PATH

echo ""
echo "***********************************************************"
echo " Starting Jellyplus Services                               "
echo "                                                           "
echo " Jellyplus will be available on: http://localhost:8096     "
echo "                                                           "
echo "***********************************************************"

## Start Supervisord
supervisord -c /etc/supervisor/supervisord.conf