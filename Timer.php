<?php
/**
 * @package Base
 */

/**
 * Simple timer for rate control purposes;
 * rates are measured by actions per day.
 *
 * @property int $rate Current actions rate
 * @property-read int $time Interval between last start/stop sequence
 *
 * @package Base
 */
class Timer extends Base_Abstract
{
    /**
     * Default rate is 0, no intervals
     */
    const DEFAULT_RATE = 0;


    /**
     * Timer start timestamp
     *
     * @var int
     */
    protected $_start = 0;

    /**
     * Timer end timestamp
     *
     * @var int
     */
    protected $_stop = 0;

    /**
     * Desired action duration, in seconds
     *
     * @var int
     */
    protected $_duration = 0;


    static public function get_rate_duration($rate)
    {
        return (0 < $rate)
            ? (int)(24 * 3600 / $rate)
            : 0;
    }

    static public function get_duration_rate($duration)
    {
        return (0 < $duration)
            ? (int)(24 * 3600 / $duration)
            : 0;
    }


    /**
     * Constructs a timer instance
     *
     * @param int $rate Optional rate in actions/day
     */
    public function __construct($rate=self::DEFAULT_RATE)
    {
        $this->set_rate($rate);
    }

    /**
     * Destructs the instance
     */
    public function __destruct()
    {
        $this->stop(false);
    }

    /**
     * Returns interval between last start-stop sequence in seconds
     *
     * @return int
     */
    public function get_time()
    {
        return max(0, $this->_stop - $this->_start);
    }

    public function set_duration($duration)
    {
        $this->_duration = max(0, (int)$duration);
        return $this;
    }

    public function get_duration()
    {
        return $this->_duration;
    }

    /**
     * Sets current rate in actions/day
     *
     * @param int $rate
     */
    public function set_rate($rate)
    {
        $this->_duration = $this->get_rate_duration($rate);
        return $this;
    }

    /**
     * Returns current rate in actions/day
     *
     * @return int
     */
    public function get_rate()
    {
        return $this->get_duration_rate($this->_duration);
    }

    /**
     * Starts the timer
     */
    public function start()
    {
        $this->_start = time();
        return $this;
    }

    /**
     * Stops the timer pausing the process if needed
     *
     * @param bool $do_sleep Sleep (pause) to meet the rate
     */
    public function stop($do_sleep=true)
    {
        $this->_stop = time();
        if ($do_sleep) {
            $this->sleep();
        }
        return $this;
    }

    /**
     * Sleeps to meet the desired rate
     */
    public function sleep()
    {
        if ($this->_duration) {
            $n = $this->_duration - ($this->_stop - $this->_start);
            if (0 < $n) {
                sleep($n);
            }
        }
        return $this;
    }
}
