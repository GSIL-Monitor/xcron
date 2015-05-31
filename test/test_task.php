<?php

include '../src/date.php';
include '../src/task.php';
include '../src/logger.php';
include '../src/exception.php';

$GLOBALS['debug'] = 1;

function test_at_plan()
{/*{{{*/
    $rule = '15 * * * * echo 1';
    $date = new CronSimpleDate(strtotime('2013/03/31 2:05:00'));
    $task = CronTask::createTask($rule, $date);
    assert($task);

    $rule = '* 23 * * * echo 1';
    $date = new CronSimpleDate(strtotime('2013/03/31 2:05:00'));
    $task = CronTask::createTask($rule, $date);
    assert($task);

    $rule = '* 23 31 * * echo 1';
    $date = new CronSimpleDate(strtotime('2013/03/31 2:05:00'));
    $task = CronTask::createTask($rule, $date);
    assert($task);

    $rule = '* 23 31 12 * echo 1';
    $date = new CronSimpleDate(strtotime('2013/03/31 2:05:00'));
    $task = CronTask::createTask($rule, $date);
    assert($task);

    $rule = '* 23 31 12 1 echo 1';
    $date = new CronSimpleDate(strtotime('2013/03/31 2:05:00'));
    $task = CronTask::createTask($rule, $date);

    $rule = '5 * * * * echo 1';
    $date = new CronSimpleDate(strtotime('2013/03/31 2:05:00'));
    $task = CronTask::createTask($rule, $date);
    assert($task);

    $date = new CronSimpleDate(strtotime('2013/03/31 4:05:00'));
    assert($task->isReady($date));
    $task->setIsRunning($date);

    $date = new CronSimpleDate(strtotime('2013/03/1 0:05:00'));
    assert(!$task->isReady($date));
    $task->setIsRunning($date);

    $date = new CronSimpleDate(strtotime('2014/02/31 23:05:00'));
    assert($task->isReady($date));
    $task->setIsRunning($date);

    $date = new CronSimpleDate(strtotime('2011/02/31 23:03:00'));
    assert(!$task->isReady($date));
    $task->setIsRunning($date);

    $rule = '5 4 * * * echo 1';
    $date = new CronSimpleDate(strtotime('2013/03/31 2:05:00'));
    $task = CronTask::createTask($rule, $date);
    $date = new CronSimpleDate(strtotime('2013/03/31 4:05:00'));
    assert($task->isReady($date));
    $task->setIsRunning($date);
    $date = new CronSimpleDate(strtotime('2013/03/1 4:05:00'));
    assert(!$task->isReady($date));
    $task->setIsRunning($date);

    $rule = '* 4 * * * echo 1';
    $date = new CronSimpleDate(strtotime('2013/03/31 2:05:00'));
    $task = CronTask::createTask($rule, $date);
    $date = new CronSimpleDate(strtotime('2013/03/31 4:05:00'));
    assert($task->isReady($date));
    $task->setIsRunning($date);

    $date = new CronSimpleDate(strtotime('2013/03/31 4:14:00'));
    assert($task->isReady($date));
    $task->setIsRunning($date);

    $rule = '*/5 * * * 2-4 echo 1';
    $date = new CronSimpleDate(strtotime('2013/03/31 2:05:00'));
    $task = CronTask::createTask($rule, $date);

    $date = new CronSimpleDate(strtotime('2013/04/2 4:05:00'));
    assert($task->isReady($date));
    $task->setIsRunning($date);

    $date = new CronSimpleDate(strtotime('2013/04/3 4:05:00'));
    assert($task->isReady($date));
    $task->setIsRunning($date);

    $date = new CronSimpleDate(strtotime('2013/04/4 4:05:00'));
    assert($task->isReady($date));
    $task->setIsRunning($date);

    $date = new CronSimpleDate(strtotime('2013/04/4 4:06:00'));
    assert(!$task->isReady($date));

    $date = new CronSimpleDate(strtotime('2013/04/4 4:10:00'));
    assert($task->isReady($date));
    $task->setIsRunning($date);

    $date = new CronSimpleDate(strtotime('2013/04/5 4:05:00'));
    assert(!$task->isReady($date));

    $date = new CronSimpleDate(strtotime('2013/04/6 4:05:00'));
    assert(!$task->isReady($date));

    $rule = '*/5 * * * 1 echo 1';
    $date = new CronSimpleDate(strtotime('2013/03/31 2:05:00'));
    $task = CronTask::createTask($rule, $date);

    $date = new CronSimpleDate(strtotime('2013/04/1 4:10:00'));
    assert($task->isReady($date));
    $task->setIsRunning($date);

    $date = new CronSimpleDate(strtotime('2013/04/2 4:10:00'));
    assert(!$task->isReady($date));
    $task->setIsRunning($date);

    $rule = '1,2,3 * * * * echo 1';
    $task = CronTask::createTask($rule);

    $date = new CronSimpleDate(strtotime('2013/04/1 4:01:00'));
    assert($task->isReady($date));
    $task->setIsRunning($date);
    $date = new CronSimpleDate(strtotime('2013/04/1 4:02:00'));
    assert($task->isReady($date));
    $task->setIsRunning($date);
    $date = new CronSimpleDate(strtotime('2013/04/1 4:03:00'));
    assert($task->isReady($date));
    $task->setIsRunning($date);
    $date = new CronSimpleDate(strtotime('2013/04/1 4:04:00'));
    assert(!$task->isReady($date));
    $task->setIsRunning($date);

    $rule = '1,2,3 * * * 4 echo 1';
    $task = CronTask::createTask($rule);
    $date = new CronSimpleDate(strtotime('2015/01/14 4:03:00'));
    assert(!$task->isReady($date));
    $date = new CronSimpleDate(strtotime('2015/01/08 4:02:00'));
    assert($task->isReady($date));
    $task->setIsRunning($date);
    $date = new CronSimpleDate(strtotime('2015/01/15 4:02:00'));
    assert($task->isReady($date));
    $task->setIsRunning($date);
}/*}}}*/

function test_intval_plan()
{/*{{{*/
    $rule = '*/5 * * * * echo 1';
    $date = new CronSimpleDate(strtotime('2013/03/31 2:05:00'));
    $task = CronTask::createTask($rule, $date);
    $date = new CronSimpleDate(strtotime('2013/03/31 4:05:00'));
    assert($task->isReady($date));
    $task->setIsRunning($date);
    $date = new CronSimpleDate(strtotime('2013/03/31 4:10:00'));
    assert($task->isReady($date));
    $task->setIsRunning($date);
    $date = new CronSimpleDate(strtotime('2013/03/31 4:14:00'));
    assert(!$task->isReady($date));
    $rule = '*/50 * * * * echo 1';
    $task = CronTask::createTask($rule);
    $date = new CronSimpleDate(strtotime('2013/03/31 4:05:00'));
    assert($task->isReady($date));
    $task->setIsRunning($date);
    $date = new CronSimpleDate(strtotime('2013/03/31 4:10:00'));
    assert(!$task->isReady($date));
    $date = new CronSimpleDate(strtotime('2013/03/31 4:55:00'));
    assert($task->isReady($date));
    $task->setIsRunning($date);
    $date = new CronSimpleDate(strtotime('2013/03/31 5:45:00'));
    assert($task->isReady($date));
}/*}}}*/

function test_area_plan()
{/*{{{*/
    $rule = '*/5 10-21 * * * echo 1';
    $date = new CronSimpleDate(strtotime('2013/03/31 2:05:00'));
    $task = CronTask::createTask($rule, $date);

    $date = new CronSimpleDate(strtotime('2013/03/31 11:05:00'));
    assert($task->isReady($date));
    $task->setIsRunning($date);
    
    $date = new CronSimpleDate(strtotime('2013/03/31 4:10:00'));
    assert(!$task->isReady($date));

    $date = new CronSimpleDate(strtotime('2013/03/31 11:14:00'));
    assert($task->isReady($date));
    $task->setIsRunning($date);

    $date = new CronSimpleDate(strtotime('2013/03/31 11:19:00'));
    assert($task->isReady($date));
    $task->setIsRunning($date);

    $date = new CronSimpleDate(strtotime('2013/03/31 11:23:00'));
    assert(!$task->isReady($date));

    $date = new CronSimpleDate(strtotime('2013/03/31 11:24:00'));
    assert($task->isReady($date));
    $task->setIsRunning($date);

    $date = new CronSimpleDate(strtotime('2013/04/2 11:23:00'));
    assert($task->isReady($date));
    $task->setIsRunning($date);

    $date = new CronSimpleDate(strtotime('2013/03/31 22:19:00'));
    assert(!$task->isReady($date));

    $rule = '* * 4-6 * * echo 1';
    $date = new CronSimpleDate(strtotime('2013/03/03 1:10:11'));
    $task = CronTask::createTask($rule, $date);

    $date = new CronSimpleDate(strtotime('2013/03/03 1:11:00'));
    assert(!$task->isReady($date));

    $date = new CronSimpleDate(strtotime('2013/03/04 1:11:00'));
    assert($task->isReady($date));
    $task->setIsRunning($date);

    $date = new CronSimpleDate(strtotime('2013/03/07 1:11:00'));
    assert(!$task->isReady($date));
    
    $rule = '4-5 * * * * echo 1';
    $task = CronTask::createTask($rule);

    $date = new CronSimpleDate(strtotime('2013/03/03 1:4:00'));
    assert($task->isReady($date));
    $task->setIsRunning($date);
    $date = new CronSimpleDate(strtotime('2013/03/03 1:5:00'));
    assert($task->isReady($date));
    $task->setIsRunning($date);
}/*}}}*/

//test
test_at_plan();
test_intval_plan();
test_area_plan();
