Summary: A crontab like programme.
Name: qcron
Version: 0.1.0
Release: 2
License: GPL
Group: QIHOO/YUNPAN
Source: qcron-0.1.0.tar.gz

%description
A crontab like programme

%prep

%setup -q

%build

%install
mkdir -p $RPM_BUILD_ROOT/usr/local/qcron/bin/
mkdir -p $RPM_BUILD_ROOT/usr/local/qcron/src/
mkdir -p $RPM_BUILD_ROOT/usr/local/qcron/conf/
mkdir -p $RPM_BUILD_ROOT/usr/local/qcron/pids/
mkdir -p $RPM_BUILD_ROOT/usr/local/qcron/logs/
install -m 755 bin/qcron $RPM_BUILD_ROOT/usr/local/qcron/bin/qcron
install conf/conf.php $RPM_BUILD_ROOT/usr/local/qcron/conf/conf.php
install src/crond.php $RPM_BUILD_ROOT/usr/local/qcron/src/crond.php
install src/date.php $RPM_BUILD_ROOT/usr/local/qcron/src/date.php
install src/exception.php $RPM_BUILD_ROOT/usr/local/qcron/src/exception.php
install src/logger.php $RPM_BUILD_ROOT/usr/local/qcron/src/logger.php
install src/main.php $RPM_BUILD_ROOT/usr/local/qcron/src/main.php
install src/parser.php $RPM_BUILD_ROOT/usr/local/qcron/src/parser.php
install src/server.php $RPM_BUILD_ROOT/usr/local/qcron/src/server.php
install src/shell.php $RPM_BUILD_ROOT/usr/local/qcron/src/shell.php
install src/task.php $RPM_BUILD_ROOT/usr/local/qcron/src/task.php

%clean
%files
%defattr(-,root,root)
/usr/local/qcron/bin/qcron
/usr/local/qcron/conf/conf.php
/usr/local/qcron/src/crond.php
/usr/local/qcron/src/date.php
/usr/local/qcron/src/exception.php
/usr/local/qcron/src/logger.php
/usr/local/qcron/src/main.php
/usr/local/qcron/src/parser.php
/usr/local/qcron/src/server.php
/usr/local/qcron/src/shell.php
/usr/local/qcron/src/task.php
/usr/local/qcron/pids/
/usr/local/qcron/bin/
/usr/local/qcron/conf/
/usr/local/qcron/logs/
/usr/local/qcron/src/
/usr/local/qcron/
