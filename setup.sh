#!/bin/bash

entropypath='/var/www/entropy'
htuser='www-data'
htgroup='www-data'
rootuser='root'

printf "Creating possible missing Directories\n"
mkdir -p $entropypath/data
mkdir -p $entropypath/assets
mkdir -p $entropypath/updater

printf "chmod Files and Directories\n"
find ${entropypath}/ -type f -print0 | xargs -0 chmod 0640
find ${entropypath}/ -type d -print0 | xargs -0 chmod 0750

printf "chown Directories\n"
chown -R ${rootuser}:${htgroup} ${entropypath}/
chown -R ${htuser}:${htgroup} ${entropypath}/apps/
chown -R ${htuser}:${htgroup} ${entropypath}/assets/
chown -R ${htuser}:${htgroup} ${entropypath}/config/
chown -R ${htuser}:${htgroup} ${entropypath}/data/
chown -R ${htuser}:${htgroup} ${entropypath}/themes/
chown -R ${htuser}:${htgroup} ${entropypath}/updater/

chmod +x ${entropypath}/occ


printf "chmod/chown .htaccess\n"
if [ -f ${entropypath}/.htaccess ]
then
 chmod 0644 ${entropypath}/.htaccess
 chown ${rootuser}:${htgroup} ${entropypath}/.htaccess
fi

if [ -f ${entropypath}/data/.htaccess ]
then
 chmod 0644 ${entropypath}/data/.htaccess
 chown ${rootuser}:${htgroup} ${entropypath}/data/.htaccess
fi