<?php
/**
 * @package Actor
 */

/**
 * @package Actor
 * @subpackage Live
 */
class Actor_Bing_Searcher extends Actor_Bing implements Actor_Interface_Searcher
{
    const BING_SEARCH_URL = '/search';

    const RESULTS_PER_PAGE = 10;


    protected $_pattern = '';
    protected $_page = 0;


    public function set_pattern($pattern)
    {
        $pattern = (string)$pattern;
        if ($this->_pattern != $pattern) {
            $this->_pattern = $pattern;
            $this->_page = 0;
        }
        return $this;
    }

    public function get_pattern()
    {
        return $this->_pattern;
    }

    public function set_page($page)
    {
        $this->_page = max(0, (int)$page);
        return $this;
    }

    public function get_page()
    {
        return $this->_page;
    }

    public function search($pattern=null, $page=null)
    {
        if (null !== $pattern) {
            $this->set_pattern($pattern);
        }
        if (null !== $page) {
            $this->set_page($page);
        }

        $this->log("Searching for {$this->_pattern}, page {$this->_page}");

        $this->get(self::BING_HOST . self::BING_SEARCH_URL, array(
            'q'     => &$this->_pattern,
            'go'    => '',
            'filt'  => 'all',
            'qs'    => 'n',
            'sk'    => '',
            'first' => $this->_page * self::RESULTS_PER_PAGE + 1,
            'FORM'  => 'PERE' . (string)rand(1, 9),
        ), self::BING_HOST . '/');
        $this->_dump(sha1($this->_pattern) . ".{$this->_page}.html");
        if (preg_match_all(
            '#<div class="sb_tlst"><h3><a.+?href="([^"]+)#',
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
