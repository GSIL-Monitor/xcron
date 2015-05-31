<?php

include '../src/parser.php';
include '../src/task.php';
include '../src/date.php';
include '../src/exception.php';
include '../src/logger.php';

$task = "0 10 * * 2 (/usr/bin/lockf -t 0 /home/q/system/cloudopenapi/logs/clean.lock /usr/local/php/bin/php /home/q/system/cloudopenapi/src/task/tool/clean_expired_data.php) >> /dev/null 2>&1 &";

$task = CronTask::createTask($task, null, null, null);

print_r($task);
