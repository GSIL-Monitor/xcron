<?php
class CronPlanParser
{
    const MAX_STARS = 5;

    public static $check_sum = '';
    private static $starFlag = array(1 => 'minute', 2 => 'hour', 3 => 'day', 4 => 'month', 5 => 'year');

    public static function parseFile($file)
    {/*{{{*/
        self::$check_sum = '';
        return self::parse($file);
    }/*}}}*/

    public static function parse($file)
    {/*{{{*/
        if (!file_exists($file) || filesize($file) === 0)
        {
            return false;
        }

        $data = json_decode(file_get_contents($file), true);
        self::$check_sum = md5(json_encode($data));

        if (empty($data))
        {
            return array();
        }

        $execute_plan = array();

        foreach($data as $task)
        {
            $task = CronTask::createTask($task['task'], $task['user'], $task['id'], $task['running_user']);
            if ($task)
            {
                $execute_plan[] = $task;
            }
        }

        return $execute_plan;
    }/*}}}*/

}
