<?php
class PHPCrond 
{
    const MIN_TIME_INTVAL = 500000;

    public $task_queue = array();
    public $pid_file = '';
    public $conf_file = '';
    public $conf_file_mtime = 0;

    public $master_process_id = false;
    public $listen_process_id = false;
    public $agent_process_id = false;

    public function __construct($conf_file)
    {/*{{{*/
        $this->pid_file = CROND_PID_FILE;
        $this->conf_file = $conf_file;
        pcntl_signal(SIGTERM, array($this, 'signalHandle'));
    }/*}}}*/

    public function signalHandle($sig)
    {/*{{{*/
        CronLogger::err("recive signal $sig");
        if ($sig == SIGTERM)
        {
            if (posix_getpid() === $this->master_process_id) // parent process
            {
                if ($this->listen_process_id)
                {
                    CronLogger::err("Kill monitor " . $this->listen_process_id);
                    posix_kill($this->listen_process_id, 9);
                }

                if ($this->agent_process_id)
                {
                    CronLogger::err("Kill agent " . $this->agent_process_id);
                    posix_kill($this->agent_process_id, 9);
                }

                if (file_exists($this->pid_file))
                {
                    @unlink($this->pid_file);
                }
            }
            exit;
        }
    }/*}}}*/

    public function addTask($task_info)
    {/*{{{*/
        if (!empty($task_info))
        {
            $this->task_queue[] = $task_info;
        }
    }/*}}}*/

    public function start($daemon = false)
    {/*{{{*/
        if ($daemon)
        {
            if (!$this->daemonize())
            {
                return false;
            }
        }

        $this->master_process_id = posix_getpid();

        $this->startServer();
        $this->startAgent();

        while(true)
        {
            $pid = pcntl_wait($status, WNOHANG);
            switch ($pid)
            {
            case $this->listen_process_id:
                CronLogger::err("Cron server not running[$status], starting ...");
                $this->startServer();
                CronLogger::err("Cron server restart done.");
                break;
            case $this->agent_process_id:
                CronLogger::err("Cron agent not running[$status], starting ...");
                $this->startAgent();
                CronLogger::err("Cron server restart done.");
                break;
            case -1:
                posix_kill($this->listen_process_id, 9);
                posix_kill($this->agent_process_id, 9);
                $this->startAgent();
                $this->startServer();
                break;
            }

            sleep(3);
        }
    }/*}}}*/

    public function updateTasks()
    {/*{{{*/
        // Get new file status every time.
        clearstatcache();

        if (!file_exists($this->conf_file) || filesize($this->conf_file) === 0)
        {
            unset($this->task_queue);
            $this->task_queue = null;
            return false;
        }

        $old_check_sum = CronPlanParser::$check_sum;
        $task_list = CronPlanParser::parseFile($this->conf_file);
        $new_check_sum = CronPlanParser::$check_sum;

        if ($old_check_sum != $new_check_sum)
        {
            foreach ((array)$task_list as $task)
            {
                CronLogger::info('New tasks: [' . $task->rule . ']');
            }

            unset($this->task_queue);
            $this->task_queue = $task_list;
        }

        unset($task_list);
        return true;
    }/*}}}*/

    public function doTasks()
    {/*{{{*/
        if (!is_array($this->task_queue) || empty($this->task_queue))
        {
            return false;
        }

        // freeze time
        $date = new CronSimpleDate();
        foreach ($this->task_queue as $task)
        {
            if ($task->isReady($date))
            {
                $pid = $this->_doTask($task);
                if (pcntl_waitpid($pid, $status) !== $pid)
                {
                    CronLogger::err("Wait pid $pid failed");
                }
            }
        }
    }/*}}}*/

    private function _doTask($task)
    {/*{{{*/
        $task->setIsRunning();
        $pid = pcntl_fork();
        if ($pid === 0)
        {
            $child_pid = pcntl_fork();
            if ($child_pid === 0)
            {
                CronLogger::info("Start task " . $task->rule);
                $task->run();
                $task->report();
            }
            else if ($child_pid < 0)
            {
                CronLogger::err("Start task " . $task->rule . " failed");
            }
            exit(0);
        }
        else if ($pid < 0)
        {
            CronLogger::err("Start task " . $task->rule . " failed");
        }
        return $pid;
    }/*}}}*/

    private function daemonize()
    {/*{{{*/
        global $daemon;
        $daemon = true;

        $pid = pcntl_fork();
        if($pid)
        {   
            exit(0);
        }
        else if ($pid < 0)
        {
            CronLogger::err("Fork failed\n");
            exit(1);
        }

        chdir(dirname(__FILE__));
        $sid = posix_setsid();
        if (!$sid)
        {
            CronLogger::err("Set sid failed\n");
            exit(1);
        }

        if (!$this->createPidFile())
        {
            return false;
        }

        fclose(STDIN);
        fclose(STDOUT);
        fclose(STDERR);

        global $STDOUT;
        global $STDERR;

        $STDOUT = fopen('/dev/null', 'w');
        $STDERR = $STDOUT;

        return true;
    }/*}}}*/

    public function startAgent()
    {/*{{{*/
        $pid = pcntl_fork();
        if ($pid === 0)
        {
            while(true)
            {
                $this->updateTasks();
                $this->doTasks();
                usleep(self::MIN_TIME_INTVAL);
            }
            exit;
        }
        else if ($pid === -1)
        {
            fwrite(STDERR, "Create process failed");
            return false;
        }
        $this->agent_process_id = $pid;
        return true;
    }/*}}}*/

    private function startServer()
    {/*{{{*/
        $server = new CronServer($this->conf_file);
        $this->listen_process_id = $server->start();
    }/*}}}*/

    public function createPidFile()
    {/*{{{*/
        if (file_exists($this->pid_file))
        {
            echo "Process exists\n";
            return false;
        }

        $path = dirname($this->pid_file);
        if (!file_exists($path))
        {
            mkdir($path, 0777, true);
        }

        if (!touch($this->pid_file))
        {
            echo "Create $pid_file failed\n";
            return false;
        }

        file_put_contents($this->pid_file, posix_getpid());
        return true;
    }/*}}}*/

}
