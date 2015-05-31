<?php
require '/home/q/php/kafka_client/lib/kafka_client.php';

class CronLogger {
    public static function info($message) 
    {/*{{{*/
        $message = '[info] ' . $message;
        $file = TASK_LOG_PATH . "/task_info.log." . date('Ymd');
        self::log($message, $file);
    }/*}}}*/

    public static function warn($message) 
    {/*{{{*/
        $message = '[warn] ' . $message;
        $file = TASK_LOG_PATH . "/task_warn.log." . date('Ymd');
        self::log($message, $file);
    }/*}}}*/

    public static function err($message) 
    {/*{{{*/
        $message = '[err] ' . $message;
        $file = TASK_LOG_PATH . "/task_err.log." . date('Ymd');
        self::log($message, $file);
    }/*}}}*/

    public static function debug($message)
    {/*{{{*/
        if (isset($GLOBALS['debug']) && $GLOBALS['debug'])
        {
            if (is_array($message))
            {
                print_r($message) . "\n";
            }
            else
            {
                echo $message . "\n";
            }
        }
    }/*}}}*/

    private static function log($message, $file)
    {/*{{{*/
        global $daemon;

        $date = date("[Y/m/d H:i:s]");
        $pid = posix_getpid();
        $user = posix_getpwuid(posix_getuid());
        $name = $user['name'];

        $message = $date . " [$name:$pid] " . $message . "\n";
        if ($daemon)
        {
            error_log($message, 3, $file);
        }
        else 
        {
            echo $message;
        }
    }/*}}}*/

    public static function report($topic, $msg, $email = '')
    {/*{{{*/
        $sysinfo = posix_uname();
        $nodename = $sysinfo['nodename'];

        if (empty($email))
        {
            $email = ALARM_EMAIL;
        }
        return mail($email, 'phpcron-' . $nodename . '-' . $topic, $msg);
    }/*}}}*/

    public static function message($message)
    {/*{{{*/
        $message = json_encode($message);
        $kfk = new Kafka_Producer(QBUS_SERVER);
        $kfk->asyncSend(array($message), 'qcron-task-log', $errmsg, 1);
    }/*}}}*/

}


