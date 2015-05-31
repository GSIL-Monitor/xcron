<?php

class CronServer
{
    const ENCRYPT_KEY = '75c4cb98e6609710540c1ca078d7a767';

    private $_bind_port = false;
    private $_sock = false;
    private $_client = false;
    private $conf_file = false;

    const BUF_SIZE = 1024;

    public function __construct($conf_file)
    {/*{{{*/
        $this->_bind_port = BIND_PORT;
        $this->_sock = @socket_create_listen($this->_bind_port);
        $this->conf_file = $conf_file;
    }/*}}}*/

    public function listen()
    {/*{{{*/
        if (!is_resource($this->_sock))
        {
            return false;
        }
        $this->_client = socket_accept($this->_sock);
        return is_resource($this->_client);
    }/*}}}*/

    public function getData()
    {/*{{{*/
        $data = '';
        while (($read_data = @socket_read($this->_client, self::BUF_SIZE, PHP_BINARY_READ)) !== false)
        {
            if ($read_data === "") break; // if no more data, socket_read return empty string
            $data .= $read_data;
        }

        if (empty($data))
        {
            CronLogger::info(socket_strerror(socket_last_error()) . "\n");
        }
        else
        {
            $data = self::decrypt($data);
            CronLogger::info($data); 
        }

        return json_decode($data, true);
    }/*}}}*/

    public function start()
    {/*{{{*/
        $pid = pcntl_fork();
        if ($pid === 0)
        {
            while ($this->listen()) 
            {
                $data = $this->getData();
                $this->run($data);
            }
            exit;
        }
        else if ($pid === -1)
        {
            fwrite(STDERR, "Create process failed");
            return false;
        }
        return $pid;
    }/*}}}*/

    public function run($data)
    {/*{{{*/
        switch ($data['type'])
        {
        case 'tasks':
            $conf = json_encode($data['data']);
            file_put_contents($this->conf_file, $conf);
            CronLogger::info("Get new tasks: " . $conf . "\n");
            CronLogger::message(array(
                'type' => 'host-sync', 
                'host' => $data['host'], 
                'user' => $data['user'], 
                'time' => time())
            );
            break;
        case 'cmd':
            $this->handleCommand($data);
            break;
        case 'ping':
            CronLogger::message(array('type' => 'ping', 'host' => php_uname('n'), 'version' => VERSION));
            break;
        }
    }/*}}}*/

    public function handleCommand($data)
    {/*{{{*/
        $ret = true;

        switch ($data['command'])
        {
        case 'stop':
            $pid = (int) file_get_contents($this->pid_file);
            if ($pid)
            {
                $ret = posix_kill($pid, SIGTERM);
            }
            break;
        case 'exec':
            $command_list = $data['option'];
            foreach ($command_list as $command)
            {
                $ret = CronShell::execute($command);
                $message[] =array($command => $ret);
            }
            CronLogger::message(array('type' => 'host-cmd', 'host' => $data['host'], 'user' => $data['user'], 'time' => time(), 'message' => $message));
            break;
        }

    }/*}}}*/

    public static function decrypt($value)
    {/*{{{*/
        $mode    = MCRYPT_MODE_ECB;
        $enc     = MCRYPT_RIJNDAEL_128;
        $iv_size = mcrypt_get_iv_size($enc, $mode);
        $iv      = mcrypt_create_iv($iv_size, MCRYPT_RAND);

        $encrypt_data = base64_decode(str_replace(array('-','_'),array('+','/'),$value));
        $ret = mcrypt_decrypt($enc, self::ENCRYPT_KEY, $encrypt_data, $mode, $iv);
        $ret = rtrim($ret, chr(0));
        return $ret;
    }/*}}}*/

}
