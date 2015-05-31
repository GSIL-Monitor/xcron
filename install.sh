#!/bin/sh

HOSTS="$*"
SCP="sudo -u sync360 scp "
SSH="sudo -u sync360 ssh "

# pack qcron

SVN_SRC="https://ipt.src.corp.qihoo.net/svn/asgard/360cloud/qcron/qcron-agent/trunk"
TMP_PATH=/tmp

# checkout code
svn export --force "$SVN_SRC" $TMP_PATH/qcron

if test $? -ne 0; then
    echo "svn checkout $SVN_SRC failed."
    exit 1
fi

rm -fr $TMP_PATH/qcron/build-5.sh 
rm -fr $TMP_PATH/qcron/build.sh 
rm -fr $TMP_PATH/qcron/install.sh
rm -fr $TMP_PATH/qcron/uninstall.sh
rm -fr $TMP_PATH/qcron/remote_cli.sh
rm -fr $TMP_PATH/qcron/rpmbuild
rm -fr $TMP_PATH/qcron/test

#pack
cd $TMP_PATH
tar -zcvf qcron.tar.gz qcron

if test $? -ne 0; then 
    echo "pack qcron failed."
    exit 1
fi

for host in $HOSTS
do
    echo $host
    $SCP qcron.tar.gz $host:~/
    $SSH $host "cd ~; tar -zxf qcron.tar.gz; if test -d /home/q/system/qcron; then rm -fr /home/q/system/qcron; fi; mv -f qcron /home/q/system/; /home/q/system/qcron/bin/qcron -d"
    echo "done"
done
