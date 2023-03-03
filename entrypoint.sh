#!/usr/bin/env bash

set -e

# 获取 apollo 配置
cd /var/www/ && php artisan command:RefreshConfiguration

#增加定时计划
cat /etc/crontab.bak > /etc/crontab.tmp
echo "
# 每分钟拉取apollo配置
* * * * * root cd /var/www/ && php artisan command:RefreshConfiguration >> /var/www/storage/logs/crontab.log
" >> /etc/crontab.tmp
mv /etc/crontab.tmp /etc/crontab
service cron restart

#php-fpm
exec php-fpm
