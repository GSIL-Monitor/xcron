<?php
class CronSimpleDate {
    public $weekday = 0;
    public $month = 0;
    public $day = 0;
    public $hour = 0;
    public $minute = 0;
    public $second = 0;
    public $year = 0;

    public function __construct($utc = null)
    {/*{{{*/
        $time = $utc ? $utc : time();
        $this->weekday = date("w", $time);
        $this->month = date("m", $time);
        $this->day = date("d", $time);
        $this->hour = date("H", $time);
        $this->minute = date("i", $time);
        $this->second = date("s", $time);
        $this->year = date("Y", $time);
    }/*}}}*/

    public function getDate()
    {/*{{{*/
        return array(
            'weekday' => $this->weekday,
            'year' => $this->year,
            'month' => $this->month,
            'day' => $this->day,
            'hour' => $this->hour,
            'munite' => $this->minute,
            'second' => $this->second,
        );
    }/*}}}*/

    public function getDateAsString($format = 'Y/m/d H:i:s')
    {/*{{{*/
        return date($format, $this->getTimeStamp());
    }/*}}}*/

    public function getTimeStamp()
    {/*{{{*/
        $format_time = $this->year . '-' . $this->month . '-' . $this->day . ' ' . $this->hour . ':' . $this->minute . ':' . $this->second;
        return strtotime($format_time);
    }/*}}}*/

    public function compare($date)
    {/*{{{*/
        static $properties = array('year', 'month', 'day', 'hour', 'minute');

        foreach ($properties as $prop)
        {
            if ($this->$prop !== $date->$prop)
            {
                return (int) $this->$prop - (int) $date->$prop;
            }
        }

        return 0;
    }/*}}}*/

    public static function check_prop($prop, $val)
    {/*{{{*/
        $is_valid = true;
        $val = (int) $val;

        switch ($prop)
        {
        case 'weekday':
            $is_valid = ($val <= 6 && $val >= 0);
            break;
        case 'month':
            $is_valid = ($val <= 12 && $val >= 1);
            break;
        case 'day':
            $is_valid = ($val <= 31 && $val >= 1);
            break;
        case 'hour':
            $is_valid = ($val <= 23 && $val >= 0);
            break;
        case 'minute':
            $is_valid = ($val <= 59 && $val >= 0);
            break;
        case 'second':
            $is_valid = ($val <= 59 && $val >= 0);
            break;
        default:
            $is_valid = false;
        }
        
        return $is_valid;
    }/*}}}*/

}
