<?php

class CronShell {
    public static $descspec = array(
        0 => array('pipe', 'r'), // standard input
        1 => array('pipe', 'w'), // standard output
        2 => array('pipe', 'w'), // standard error output
    );

    public static function execute($command, $env = null)
    {/*{{{*/
        $process = proc_open($command, self::$descspec, $pipes, null, $env);

        if (!is_resource($process))
        {
            return false;
        }

        // Close standard input
        fclose($pipes[0]);

        $execute_result = stream_get_contents($pipes[1], 1024);
        $error_message = stream_get_contents($pipes[2], 1024);

        fclose($pipes[1]);
        fclose($pipes[2]);

        do {
            $status = proc_get_status($process);
            usleep(10 * 1000);
        } while($status['running']);

        $exit_code = -1;

        if ($status)
        {
            $exit_code = $status['exitcode'];
        }

        proc_close($process);
        return array('result' => $execute_result, 'error' => $error_message, 'exit_code' => $exit_code);
    }/*}}}*/

}
