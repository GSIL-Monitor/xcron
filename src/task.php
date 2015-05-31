<?php
class CronTask 
{
    const MAX_TOKENS = 5;

    public $user = null;
    public $id = null;

    public $running_user = 'sync360';

    public $at_weekday = array();
    public $at_month = array();
    public $at_day = array();
    public $at_hour = array();
    public $at_minute = array();

    public $start_weekday= -1;
    public $start_month = -1;
    public $start_day = -1;
    public $start_hour = -1;
    public $start_minute = -1;

    public $end_weekday = -1;
    public $end_month = -1;
    public $end_day = -1;
    public $end_hour = -1;
    public $end_minute = -1;

    public $intval_weekday = 0;
    public $intval_month = 0;
    public $intval_day = 0;
    public $intval_hour = 0;
    public $intval_minute= 0;

    public $command = "";
    public $rule = "";

    public $last_execute_date = null;
    public $last_execute_info = '';
    public $next_execute_date = null;

    // for parsing rule
    private $_start = 0;
    private $_tokens = array();

    public static $DATE_FORMAT = array('weekday', 'month', 'day', 'hour', 'minute');

    public static function createTask($rule, $user = null, $id = null, $running_user = null)
    {/*{{{*/
        $task = false;
        try 
        {
            $rule = trim($rule);
            $task = new CronTask($rule);
            $task->user = $user;
            $task->id = $id;
            if ($running_user)
            {
                $task->running_user = $running_user;
            }
        }
        catch (CronException $ce)
        {
            CronLogger::err($ce->getMessage() . "\n" . $ce->getTraceAsString());
            $task = false;
        }

        return $task;
    }/*}}}*/

    private function __construct($rule)
    {/*{{{*/
        $this->rule = $rule;
        $this->parseRule();
    }/*}}}*/

    public function isReady($date)
    {/*{{{*/
        // Every minute task execute once
        if ($this->last_execute_date && $this->last_execute_date->compare($date) == 0)
        {
            CronLogger::debug("Already execute " . $date->getDateAsString());
            return false;
        }

        if (!$this->hitTimePlan($date))
        {
            CronLogger::debug("Miss at time property " . $date->getDateAsString());
            return false;
        }

        if (!$this->hitTimeInterval($date))
        {
            CronLogger::debug("Miss time intval property " . $date->getDateAsString());
            return false;
        }

        if (!$this->hitTimeArea($date))
        {
            CronLogger::debug("Miss time area property " . $date->getDateAsString());
            return false;
        }

        return true;
    }/*}}}*/

    public function setIsRunning($date = null)
    {/*{{{*/
        if (null == $date)
        {
            $date = new CronSimpleDate();
        }

        $this->last_execute_date = $date;

        $timestr = '+';
        foreach (self::$DATE_FORMAT as $property)
        {
            $intval = (int) $this->{'intval_' . $property};
            if ($intval !== 0)
            {
                $timestr .= $intval . ' ' . $property . ' ';
            }
        }

        if ($timestr == '+')
        {
            $timestr = '+0 seconds';
        }

        $new_time_stamp = strtotime($timestr, $this->last_execute_date->getTimeStamp());
        $this->next_execute_date = new CronSimpleDate($new_time_stamp);
        return true;
    }/*}}}*/

    public function hitTimePlan($date)
    {/*{{{*/
        foreach (self::$DATE_FORMAT as $property)
        {
            $at_property = $this->{'at_' . $property};
            $now_property = (int) $date->{$property};

            $match = false;
            if (empty($at_property))
            {
                $match = true;
            }
            else
            {
                foreach ($at_property as $at)
                {
                    $at = (int)$at;
                    if ($at === $now_property)
                    {
                        $match = true;
                        break;
                    }
                }
            }

            if (!$match) 
            {
                CronLogger::debug("not match $property");
                return false;
            }
        }
        return true;
    }/*}}}*/

    public function hitTimeInterval($date)
    {/*{{{*/
        if ($this->next_execute_date)
        {
            return $this->next_execute_date->compare($date) <= 0 ;
        }
        return true;
    }/*}}}*/

    public function hitTimeArea($date)
    {/*{{{*/
        foreach (self::$DATE_FORMAT as $property)
        {
            $start = (int) $this->{'start_' . $property};
            $end = (int) $this->{'end_' . $property};

            $now = (int) $date->{$property};
            
            if ($start != -1 && $end != -1 && ($now > $end || $now < $start))
            {
                CronLogger::debug("miss area $property now=$now  start=$start end=$end");
                return false;
            }
        }
        return true;
    }/*}}}*/

    public function changeToRunningUser()
    {/*{{{*/
        $user = posix_getpwnam($this->running_user);

        if (empty($user))
        {
            $this->last_execute_info = array('error' => "User " . $this->running_user . " not exists on " . php_uname('n'));
            return false;
        }

        $uid = $user['uid'];
        $gid = $user['gid'];

        $ret = posix_setgid($gid);
        if (!$ret)
        {
            $this->last_execute_info = array('error' => "Unable change to gid User " . $this->running_user . " " . php_uname('n'));
            return false;
        }

        // change user
        $ret = posix_setuid($uid); // change owner 
        if (!$ret)
        {
            $this->last_execute_info = array('error' => "Unable change to uid User " . $this->running_user . " " . php_uname('n'));
            return false;
        }

        return true;
    }/*}}}*/

    public function run()
    {/*{{{*/
        if ($this->changeToRunningUser())
        {
            $this->last_execute_info = CronShell::execute($this->command);
        }
    }/*}}}*/

    public function report()
    {/*{{{*/
        CronLogger::info(json_encode($this->last_execute_info));
        CronLogger::message(array('type' => 'task-run', 'id' => $this->id, 'user' => $this->user, 'time' => time(), 'result' => $this->last_execute_info));
    }/*}}}*/

    public function parseRule()
    {/*{{{*/
        while (($token = $this->getToken()) !== false)
        {
            $this->_tokens[] = $token;

            if (count($this->_tokens) >= self::MAX_TOKENS)
            {
                break;
            }
        }

        if (count($this->_tokens) < self::MAX_TOKENS)
        {
            throw new CronException("parse tokens failed: error token count");
        }

        $this->command = substr($this->rule, $this->_start);

        if (!$this->command)
        {
            throw new CronException("parse rule failed: no command");
        }

        if (!$this->parseTokens())
        {
            throw new CronException("parse tokens failed: " . json_encode($this->_tokens));
        }

        return true;
    }/*}}}*/

    private function getToken()
    {/*{{{*/
        $left_pos = $this->_start;

        for ($index = $this->_start; $this->rule[$index] === ' '; $index++);

        $left_pos = $index;
        $right_pos = $left_pos + 1;
        $this->_start = $index;

        for ($index = $this->_start; $this->rule[$index] !== ' ' && $this->rule[$index] !== null && $this->rule[$index] !== ""; $index++);

        $right_pos = $index;
        $this->_start = $index;

        if ($left_pos === $right_pos)
        {
            return false;
        }

        $token = substr($this->rule, $left_pos, $right_pos - $left_pos);
        return $token;
    }/*}}}*/

    private function _checkProp($prop, $val)
    {/*{{{*/
        if (!CronSimpleDate::check_prop($prop, $val))
        {
            throw new CronException("invalid $prop value: $val");
        }
    }/*}}}*/

    private function parseTokens()
    {/*{{{*/
        $token_index = array_reverse(self::$DATE_FORMAT);

        if (count($this->_tokens) != self::MAX_TOKENS)
        {
            return false;
        }

        for ($i = 0; $i < count($this->_tokens); $i ++)
        {
            $token = $this->_tokens[$i];
            $index = $token_index[$i];

            if (is_numeric($token) || strpos($token, ',') !== false)
            {
                $this->doTimeAtToken($token, $index);
            }
            else if (strpos($token, '-') !== false)
            {
                $this->doTimeAreaToken($token, $index);
            }
            else if (strpos($token, '/') !== false || $token == '*')
            {
                $this->doTimeIntvalToken($token, $index);
            }
            else 
            {
                throw new CronException("Invalid token: $token");
            }
        }

        foreach($token_index as $index)
        {
            if (empty($this->{"at_$index"}) && $this->{"start_$index"} == -1 && $this->{"intval_$index"} == 0)
            {
                $this->{"intval_$index"} = 1;
                break;
            }

            if ($this->{"intval_$index"} > 0 || !empty($this->{"at_$index"}) || $this->{"start_$index"} != -1)
            {
                break;
            }
        }

        return true;
    }/*}}}*/

    private function doTimeAreaToken($token, $index)
    {/*{{{*/
        list($start, $end) = explode('-', $token);
        if (is_numeric($start) && is_numeric($end))
        {
            $this->_checkProp($index, $start);
            $this->_checkProp($index, $end);
            $this->{'start_' . $index} = (int) $start;
            $this->{'end_'   . $index} = (int) $end;
        }
        else 
        {
            throw new CronException("Invalid token: $token");
        }
    }/*}}}*/

    private function doTimeIntvalToken($token, $index)
    {/*{{{*/
        if ($token == '*')
        {
            $this->{'intval_' . $index} = 0;
        }
        else
        {
            list($star, $intval) = explode('/', $token);
            if ($star == '*' && is_numeric($intval))
            {
                $this->_checkProp($index, $intval);
                $this->{'intval_' . $index} = (int) $intval;
            }
            else 
            {
                throw new CronException("Invalid token: $token");
            }
        }

    }/*}}}*/

    private function doTimeAtToken($token, $index)
    {/*{{{*/
        $at_tokens = explode(',', $token);
        $this->{'at_' . $index} = array();

        if (empty($at_tokens))
        {
            throw new CronException("Invalid token[$index] $token");
        }

        foreach ((array) $at_tokens as $token)
        {
            $this->_checkProp($index, $token);
            $this->{'at_' . $index}[] = $token;
        }
    }/*}}}*/

}

