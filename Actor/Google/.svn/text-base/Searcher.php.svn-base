<?php
/**
 * @package Actor
 */

/**
 * Google searcher
 *
 * @property string $pattern Search pattern, resets {@link ::$_page} to zero when set
 * @property int    $page    Search results page number to fetch
 *
 * @package Actor
 * @subpackage Google
 */
class Actor_Google_Searcher
    extends Actor_Google
    implements Actor_Interface_Searcher
{
    const SEARCH_URL = '/search';


    protected $_pattern = '';
    protected $_page = 0;


    /**
     * Sets a search pattern for next {@link ::search()} call, resets page number to 0
     *
     * @param string $pattern
     */
    public function set_pattern($pattern)
    {
        $pattern = (string)$pattern;
        if ($this->_pattern != $pattern) {
            $this->_pattern = $pattern;
            $this->_page = 0;
        }
        return $this;
    }

    /**
     * Returns current search pattern
     *
     * @return string
     */
    public function get_pattern()
    {
        return $this->_pattern;
    }

    /**
     * Sets search results page number for next {@link ::search()} call
     *
     * @param int $page
     */
    public function set_page($page)
    {
        $this->_page = max(0, (int)$page);
        return $this;
    }

    /**
     * Returns current search results page number
     *
     * @return int
     */
    public function get_page()
    {
        return $this->_page;
    }

    /**
     * Searches for specific pattern
     *
     * @return array|false List of URLs on success
     */
    public function search($pattern=null, $page=null)
    {
        if (null !== $pattern) {
            $this->pattern = $pattern;
        }
        if (null !== $page) {
            $this->page = $page;
        }

        $this->log("Searching for {$this->_pattern}, page {$this->_page}");

        $this->get(self::HOST . self::SEARCH_URL, array(
            'q'     => &$this->_pattern,
            'start' => 10 * $this->_page,
            'hl'    => 'en',
        ));
        $this->_dump("{$this->_page}.html");
        if ((false !== strpos(
            $this->_response,
            '</span>' . ($this->_page + 1) . '<'
        )) && preg_match_all(
            '#<h3 class="?r"?><a.+?href="([^"]+)#',
            $this->_response,
            $m
        )) {
            $this->_page++;
            return array_unique(array_map('html_entity_decode', $m[1], array_pad(
                array(),
                count($m[1]),
                ENT_QUOTES
            )));
        }

        $this->log('Nothing found',
                   Log_Abstract::LEVEL_ERROR);
        return false;
    }
}
