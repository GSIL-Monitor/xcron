#!/bin/sh
version='0.1.0'

tmppath="/tmp/qcron-$version"
buildroot="/usr/src/redhat"
specs="rpmbuild/SPECS/qcron.spec"

rm -frv $tmppath 
svn export https://ipt.src.corp.qihoo.net/svn/asgard/360cloud/qcron/qcron-agent/trunk $tmppath
old_pwd=`pwd`
cd /tmp

rm -frv $tmppath/test
rm -frv $tmppath/build.sh
rm -frv $tmppath/build-5.sh
rm -frv $tmppath/rpmbuild/

tar -zcvf qcron-${version}.tar.gz qcron-${version} 
cp ${tmppath}.tar.gz $buildroot/SOURCES/ -f -v

rm -fv ${tmppath}.tar.gz
rm -frv $tmppath

cd $old_pwd
rpmbuild --buildroot $buildroot/BUILDROOT -bb $specs
