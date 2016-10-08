<?php
/**
 * @package Actor
 */

/**
 * @package Actor
 * @subpackage Google
 */
class Actor_Google_NewsSearcher
    extends Actor_Google
    implements Actor_Interface_Searcher
{
    const SEARCH_URL = '/news/search';


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
     * Searches for news
     *
     * @param string $pattern
     * @param int    $page
     * @param bool   $urls_only Whether to return full news details (false)
     *                          or URLs only
     * @return array|false
     */
    public function search($pattern=null, $page=null, $urls_only=true)
    {
        if (null !== $pattern) {
            $this->pattern = $pattern;
        }
        if (null !== $page) {
            $this->page = $page;
        }

        $this->log("Looking for news on {$this->_pattern}, page {$this->_page}");

        $this->get(self::NEWS_HOST . self::SEARCH_URL, array(
            'q'       => &$this->_pattern,
            'pz'      => 1,
            'cf'      => 'all',
            'ned'     => 'us',
            'hl'      => 'en',
            'as_qdr'  => 'a',
            'as_drrb' => 'q',
            'start'   => $this->_page * 10
        ));
        $this->_dump("search.{$this->_page}.html");
        if (false === strpos(
            $this->_response,
            '<span class="num current">' . $this->_page . '</span>'
        )) {
            $this->log('No such page',
                       Log_Abstract::LEVEL_ERROR);
            return false;
        } else if (!preg_match_all(
            '#<h2 class="title">(.+?)</h2>#',
            $this->_response,
            $titles
        )) {
            $this->log('Nothing found',
                       Log_Abstract::LEVEL_ERROR);
            return false;
        }

        $this->_page++;
        $items = array();
        foreach ($titles[1] as $title) {
            if (preg_match('#<a.+?href="([^"]+)#', $title, $url)) {
                $url = html_entity_decode($url[1], ENT_QUOTES);
                $title = trim(html_entity_decode(strip_tags($title), ENT_QUOTES));
                $items[] = $urls_only
                    ? $url
                    : array(
                        'url'   => $url,
                        'title' => $title,
                      );
            }
        }
        return count($items)
            ? $items
            : false;
    }
}
