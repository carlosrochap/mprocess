<?php

class Actor_Ask_Searcher extends Actor_Ask implements Actor_Interface_Searcher
{
    const SEARCH_URL = '/web';


    protected $_pattern = '';
    protected $_page = 0;
    protected $_query_id = null;


    public function set_pattern($pattern)
    {
        $pattern = (string)$pattern;
        if ($this->_pattern != $pattern) {
            $this->_pattern = $pattern;
            $this->_page = 0;
            $this->_query_id = null;
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
            $this->pattern = $pattern;
        }
        if (null !== $page) {
            $this->page = $page;
        }
        if (!$this->_query_id) {
            $this->page = 0;
        }

        $this->log("Searching for {$this->_pattern}, page {$this->_page}");

        $query = array(
            'l'    => 'dir',
            'o'    => 312,
            'q'    => &$this->_pattern,
            'qsrc' => 0,
            'dm'   => 'all',
            'page' => $this->_page + 1,
        );
        if ($this->_query_id) {
            $query['qid'] = &$this->_query_id;
        }

        $old_follow_refresh = $this->_connection->follow_refresh;
        $this->_connection->follow_refresh = false;
        $this->get(self::HOST . self::SEARCH_URL, $query, self::HOST . '/');
        $this->_connection->follow_refresh = $old_follow_refresh;
        $this->_dump(sha1($this->_pattern) . ".{$this->_page}.html");
        if (preg_match_all(
            '#<a id="r\d+_t".+?href="([^"]+)#',
            $this->_response,
            $m
        )) {
            if (!$this->_query_id && preg_match(
                '#&qid=([\dA-Fa-f]+)#',
                $this->_response,
                $qid
            )) {
                $this->_query_id = $qid[1];
            }
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
