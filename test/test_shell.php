<?php

include '../src/shell.php';
$cmd = $argv[1];
$retval = CronShell::execute($cmd);
print_r($retval);
