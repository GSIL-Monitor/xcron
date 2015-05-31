#!/bin/sh
version='0.1.0'
release='2'
old_pwd=`pwd`

tmppath="/tmp/qcron-$version"

rm -frv $tmppath 

svn export https://ipt.src.corp.qihoo.net/svn/asgard/360cloud/qcron/qcron-agent/trunk $tmppath
cd /tmp

rm -frv $tmppath/test
rm -frv $tmppath/build.sh

rm -frv ~/rpmbuild

mv -v $tmppath/rpmbuild ~/

tar -zcvf qcron-${version}.tar.gz qcron-${version} 

cd $old_pwd

cp ${tmppath}.tar.gz ~/rpmbuild/SOURCES/ -f -v

rm -fv ${tmppath}.tar.gz
rm -frv $tmppath

cd ~/rpmbuild

rpmbuild -bb SPECS/qcron.spec
