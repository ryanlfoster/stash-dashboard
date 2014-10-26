<?php
namespace DateTime;

class WorkingHoursCalculator
{
    private $workDayBeginHour = 9;

    private $workDayEndHour = 17;

    private $holidays = array();

    public function __construct($workDayBeginHour = 9, $workDayEndHour = 17, $holidays = array())
    {
        $this->setWorkDayBeginHour($workDayBeginHour);
        $this->setWorkDayEndHour($workDayEndHour);
        $this->setHolidays($holidays);
    }

    /**
     * @param int $from timestamp
     * @param int $to timestamp
     * @return float
     */
    function getWorkingHours($from, $to)
    {
        // work day seconds
        $workday_start_hour = 9;
        $workday_end_hour = 17;
        $workday_seconds = ($workday_end_hour - $workday_start_hour) * 3600;

        // work days beetwen dates, minus 1 day
        $from_date = date('Y-m-d', $from);
        $to_date = date('Y-m-d', $to);
        $workdays_number = count($this->getWorkdays($from_date, $to_date)) - 1;
        $workdays_number = $workdays_number < 0 ? 0 : $workdays_number;

        // start and end time
        $start_time_in_seconds = date("H", $from) * 3600 + date("i", $from) * 60;
        $end_time_in_seconds = date("H", $to) * 3600 + date("i", $to) * 60;

        // final calculations
        $working_hours = ($workdays_number * $workday_seconds + $end_time_in_seconds - $start_time_in_seconds) / 86400 * 24;

        return $working_hours;
    }

    function getWorkdays($from, $to)
    {
        // arrays
        $days_array = array();
        $skipdays = array("Saturday", "Sunday");
        $skipdates = $this->getHolidays();

        // other variables
        $i = 0;
        $current = $from;

        if ($current == $to) // same dates
        {
            $timestamp = strtotime($from);
            if (!in_array(date("l", $timestamp), $skipdays) && !in_array(date("Y-m-d", $timestamp), $skipdates)) {
                $days_array[] = date("Y-m-d", $timestamp);
            }
        } elseif ($current < $to) // different dates
        {
            while ($current < $to) {
                $timestamp = strtotime($from . " +" . $i . " day");
                if (!in_array(date("l", $timestamp), $skipdays) && !in_array(date("Y-m-d", $timestamp), $skipdates)) {
                    $days_array[] = date("Y-m-d", $timestamp);
                }
                $current = date("Y-m-d", $timestamp);
                $i++;
            }
        }

        return $days_array;
    }

    /**
     * @return int
     */
    public function getWorkDayBeginHour()
    {
        return $this->workDayBeginHour;
    }

    /**
     * @param int $workDayBeginHour
     */
    public function setWorkDayBeginHour($workDayBeginHour)
    {
        $this->workDayBeginHour = $workDayBeginHour;
    }

    /**
     * @return int
     */
    public function getWorkDayEndHour()
    {
        return $this->workDayEndHour;
    }

    /**
     * @param int $workDayEndHour
     */
    public function setWorkDayEndHour($workDayEndHour)
    {
        $this->workDayEndHour = $workDayEndHour;
    }

    /**
     * @param string[] $holidays each date in Y-m-d format
     */
    public function setHolidays(array $holidays)
    {
        $this->holidays = $holidays;
    }

    public function getHolidays()
    {
        return $this->holidays;
    }
}