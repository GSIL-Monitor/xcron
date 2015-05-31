<?php

define('TASK_PROJECT_HOME', dirname(dirname(__FILE__)));
define('TASK_LOG_PATH', TASK_PROJECT_HOME . '/logs/');
define('CROND_PID_FILE', TASK_PROJECT_HOME . '/pids/qcrond.pid');
define('TASK_MAX_CHILDS', 100);
define('CRON_COMMAND', TASK_PROJECT_HOME . '/bin/cron.php');
define('BIND_PORT', 7259);
define('VERSION', '0.1.0');
define('QBUS_SERVER', 'center');
