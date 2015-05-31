#!/bin/sh

hosts="$1"
SSH="sudo -u sync360 ssh "

for host in $hosts; do
    echo $host
    $SSH $host "/home/q/system/phpcron/bin/cron.php -k"
    $SSH $host "crontab -l | grep -v phpcron | crontab | crontab -l"
done
