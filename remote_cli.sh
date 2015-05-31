#!/bin/sh

SSH="sudo -u sync360 ssh -o ConnectTimeout=3 "
QCRON_PATH="/home/q/system/qcron"

cmd=$1

shift 1

hosts="$*"

qcron_start()
{
    host=$1
    echo "check host: $host ... "
    $SSH $host "$QCRON_PATH/bin/qcron -d"
}

if [ "$cmd" == "start" ]; then
    for host in $hosts
    do
        qcron_start $host
    done
fi
