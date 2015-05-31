<?php
include TASK_PROJECT_HOME . '/src/parser.php';
include TASK_PROJECT_HOME . '/src/shell.php';
include TASK_PROJECT_HOME . '/src/task.php';
include TASK_PROJECT_HOME . '/src/date.php';
include TASK_PROJECT_HOME . '/src/exception.php';
include TASK_PROJECT_HOME . '/src/logger.php';
include TASK_PROJECT_HOME . '/src/crond.php';
include TASK_PROJECT_HOME . '/src/server.php';


function color_echo($msg, $fg = '', $bg = '', $ctrl = '')
{/*{{{*/
    $color_fg = '';
    $color_bg = '';
    $ansi_ctr = 0;
    $color_close = "\033[0m";
    $color_start_left  = "\033[";
    $color_start_right = "m";
    $ansi_ctr_left = "\033[";
    $ansi_ctr_right = "m";

    $next_line = ($msg[strlen($msg) - 1] == "\n") ? true : false;
    $message = rtrim($msg, "\n");

    static $bgcolor_map = array (
        'red' => 41,
        'black' => 40,
        'green' => 42,
        'yellow' => 43,
        'blue' => 44,
        'purple' => 45,
        'white' => 47,
    );

    static $fgcolor_map = array (
        'black' => 30,
        'red' => 31,
        'green' => 32,
        'yellow' => 33,
        'blue' => 34,
        'purple' => 35,
        'white' => 37,
    );

    static $control = array(
        'default' => 0,
        'highlight' => 1,
        'underline' => 4,
        'blink' => 5,
        'negative' => 7,
        'conceal' => 8,
    );

    $color_fg = isset($fgcolor_map[$fg]) ? $fgcolor_map[$fg] : $fgcolor_map['white'];
    $color_bg = isset($bgcolor_map[$bg]) ? $bgcolor_map[$bg] : $bgcolor_map['black'];
    $ansi_ctr = isset($control[$ctrl]) ? $control[$ctrl] : 0;

    $ansi_ctr_str = '';
    if ($ansi_ctr)
    {
        $ansi_ctr_str = $ansi_ctr_left. $ansi_ctr . $ansi_ctr_right;
    }

    $color_message = $color_start_left . $color_fg . ';' . $color_bg . $color_start_right . $ansi_ctr_str . $message . $color_close . ($next_line ? "\n" : "");
    echo $color_message;
}/*}}}*/

function edit_conf($file)
{/*{{{*/
    $desc = array (
        array('file', '/dev/tty', 'r'),
        array('file', '/dev/tty', 'w'),
        array('file', '/dev/tty', 'w'),
    );

    $process = @proc_open("vim $file", $desc, $pipes);
    proc_close($process);

    try
    {
        $ret = CronPlanParser::parse($file, true);
    }
    catch (CronException $ce)
    {
        color_echo("Error: " . $ce->getMessage() . "\n", 'red');
        return false;
    }

    return $ret;
}/*}}}*/

function sighandle($signo)
{/*{{{*/
}/*}}}*/

function get_hosts($key = false)
{/*{{{*/
    $conf_path = TASK_PROJECT_HOME . '/conf/taskconf/cluster/';
    $cmd = "find $conf_path -maxdepth 1 | grep conf";
    $input = popen($cmd, 'r');
    $clusters = array();

    while (!feof($input))
    {
        $line = trim(fgets($input));
        if ($line && strpos($line, 'svn-base') === false && !is_dir($line))
        {
            $ret = substr(basename($line), 5);
            if ($ret)
            {
                $hosts[] = $ret;
            }
        }
    }
    pclose($input);

    if (!empty($hosts))
    {
        if ($key) 
        {
            $hosts = array_values(preg_grep("/$key/", $hosts));
        }

        sort($hosts);
    }
    return $hosts;
}/*}}}*/

function get_process_status($pid)
{/*{{{*/
    $status = shell_exec("ps -o stat -p $pid | grep -v STAT");
    return trim($status);
}/*}}}*/

function get_process_childs_count($pid)
{/*{{{*/
    $childs = trim(shell_exec("ps -ef | awk '{if ($3 == $pid) print $0}' | wc -l"));
    return (int) $childs;
}/*}}}*/

function get_zombie_childs_count($pid)
{/*{{{*/
    $zombie_childs = trim(shell_exec("ps -ef | awk '{if ($3 == $pid) print $0}' | grep -c defunct"));
    return (int) $zombie_childs - 1;
}/*}}}*/

function get_zombie_childs($pid)
{/*{{{*/
    $info = shell_exec("ps -ef | awk '{if ($3 == $pid) print $0}' | grep defunct");
    return $info;
}/*}}}*/

function report($topic, $msg)
{/*{{{*/
    $sysinfo = posix_uname();
    $nodename = $sysinfo['nodename'];
    return mail('xutiecheng@360.cn', $nodename . '-' . $topic, $msg);
}/*}}}*/

function check_child_process($pid)
{/*{{{*/
    $childs = get_process_childs_count($pid);
    if ($childs > TASK_MAX_CHILDS)
    {
        report('php cron childs too much', 'child process count: ' . $childs);
    }

    $zombie_childs = get_zombie_childs_count($pid);
    if ((int)$zombie_childs > 2)
    {
        $info = get_zombie_childs($pid);
        report('php cron zombie childs', 'zombie childs count: ' . $zombie_childs . "\n" . $info);
    }
}/*}}}*/

function clean_logs()
{/*{{{*/
    $logpath = TASK_PROJECT_HOME . '/logs/';
    $cmd = "find $logpath -ctime +3 | xargs rm -fv 2>&1";
    return shell_exec($cmd);
}/*}}}*/

function add_task($task, $hosts)
{/*{{{*/
    // check task
    CronTask::parseRule($task);

    $matched_hosts = get_hosts($hosts);

    foreach ((array)$matched_hosts as $host)
    {
        color_echo("add task [$task] to $host\n", 'green');
        if (!add_task_to_host($task, $host))
        {
            color_echo("add task to $host failed\n", "red");
        }
    }

    return true;
}/*}}}*/

function get_task_process_id()
{/*{{{*/
    $pid = -1;
    if (file_exists(CROND_PID_FILE))
    {
        $pid = (int) trim(file_get_contents(CROND_PID_FILE));
    }

    return $pid;
}/*}}}*/

function get_local_conf_file()
{/*{{{*/
    $sysinfo = posix_uname();
    $nodename = $sysinfo['nodename'];
    $conf_file = TASK_PROJECT_HOME . '/conf/conf.' . $nodename;

    if (!file_exists($conf_file) && strpos($nodename, "w-") !== false)
    {
        $nodename = substr($nodename, 2);
        $conf_file = TASK_PROJECT_HOME . '/conf/conf.' . $nodename;
    }

    return $conf_file;
}/*}}}*/

function start_cron($daemon = false, $cluster = false)
{/*{{{*/
    $pid = get_task_process_id();

    if ($pid != -1 && $daemon)
    {
        check_child_process($pid);
        $status = get_process_status($pid);

        if ($status && $status != 'Ss')
        {
            $msg = date("[Y-m-d H:i:s] ") . "Error process status: $status\n";
            $try_count = 3;
            $error_status = false;

            while ($try_count -- > 0)
            {
                $status = get_process_status($pid);
                if ($status == 'Ss')
                {
                    $error_status = false;
                    break;
                }
                $error_status = true;
                sleep(1);
            }

            if ($error_status)
            {
                report('php cron', $msg);
            }
            exit;
        }
        else if ($status == '')
        {
            report('php cron', "Process $pid not found and restart\n");
            posix_kill($pid, SIGKILL);
            unlink(CROND_PID_FILE);
        }
        else 
        {
            echo "Process $pid exists\n";
            exit;
        }
    }

    $crond = new PHPCrond(get_local_conf_file());
    return $crond->start($daemon);
}/*}}}*/

function stop_cron()
{/*{{{*/
    $pid = get_task_process_id();
    if ($pid != -1)
    {
        color_echo("Stop phpcron ...\n", 'red');
        posix_kill($pid, SIGTERM);
    }
    else 
    {
        color_echo("Process not exists\n", 'red');
    }
    exit;
}/*}}}*/

function show_cron_tasks($conf_file)
{/*{{{*/
    $task_list = CronPlanParser::parse($conf_file);
    foreach ((array)$task_list as $task)
    {
        if ($task) color_echo($task->rule . "\n", 'green');
    }
    return true;
}/*}}}*/

function usage()
{/*{{{*/
    global $argv;
    color_echo("Usage: " . basename($argv[0]) . " -[dkvsh] \n", 'green');
    color_echo("   -d:  Run in background.\n", 'green');
    color_echo("   -k:  Stop master process of phpcron in background \n", 'green');
    color_echo("   -v:  Run phpcron in debug mode\n", 'green');
    color_echo("   -s:  Show tasks\n", 'green');
    color_echo("   -h:  Show this message \n", 'green');
}/*}}}*/

function get_options()
{/*{{{*/
    $opts = "def:a:t:hr:c:skvin:b:g";
    $options = getopt($opts);
    return $options;
}/*}}}*/

function install()
{/*{{{*/
    $user = trim(shell_exec('whoami'));
    $home = "/home/$user";
    $bashrc = "$home/.bashrc";

    if (file_exists($bashrc))
    {
        $path = getenv('PATH');

        if (strpos($path, CRON_COMMAND) != false)
        {
            echo CRON_COMMAND . "\n";
        }
        else 
        {
            $path = "export PATH=$path:" . CRON_COMMAND;
            file_put_contents($bashrc, "$path", FILE_APPEND);
            $ret = system("source $bashrc");
            color_echo($ret . "\n", 'red');
        }
    }
    else 
    {
        echo "$bashrc not exists\n";
    }

}/*}}}*/
