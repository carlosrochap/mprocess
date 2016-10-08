<?php
/**
 * @package Pool
 */

/**
 * @package Pool
 * @subpackage Twitter
 */
class Pool_Twitter_Trend extends Pool_Http_Abstract
{
    const TRENDS_URL = 'http://search.twitter.com/trends/current.json';


    /**
     * Fetches current Twitter trending topics
     *
     * @return array|false
     */
    protected function _fill_trend()
    {
        $response = json_decode($this->_connection->get(
            self::TRENDS_URL, array('exclude' => 'hashtags')
        ), true);
        if (!$response || !is_array(@$response['trends'])) {
            return false;
        }

        $items = array();
        $trends = current($response['trends']);
        foreach ($trends as $trend) {
            $items[] = $trend['name'];
        }
        return count($items)
            ? $items
            : false;
    }


    /**
     * @see Pool_Http_Abstract::init()
     */
    public function init()
    {
        $this->_connection = new Connection_Curl();

        return parent::init();
    }

    /**
     * Limit pool to trends
     *
     * @see Pool_Abstract::get()
     */
    public function get($key='trend')
    {
        return parent::get($key);
    }
}
