<?php
/**
 * @package Actor
 */

/**
 * @package Actor
 * @subpackage MetaCafe
 */
class Actor_MetaCafe_Grabber
    extends Actor_MetaCafe
    implements Actor_Interface_Grabber
{
    const MOST_POPULAR_URL = '/f/videos/most_popular/page-%d/';


    protected $_page = 0;


    public function set_page($page)
    {
        $this->_page = max(0, (int)$page);
        return $this;
    }

    public function get_page()
    {
        return $this->_page;
    }

    /**
     * Grabs videos from specific list
     *
     * @param string $url  Videos list URL
     * @param int    $page Optional page to grab (zero based)
     * @return array|false
     */
    public function grab($url=self::MOST_POPULAR_URL, $page=null)
    {
        if (null !== $page) {
            $this->page = $page;
        }
        $this->log("Grabbing {$url}, page {$this->_page}");

        $this->get(self::HOST . sprintf($url, $this->_page + 1));
        $this->_dump("{$this->_page}.html");
        if (preg_match_all(
            '#<a href="(?P<url>[^"]+)" title="(?P<title>[^"]+)" class="ItemThumb" >#',
            $this->_response,
            $m
        )) {
            $this->_page++;
            $videos = array();
            foreach ($m['url'] as $i => $v) {
                $a = array_map('trim', array_map(
                    'html_entity_decode',
                    array('title' => $m['title'][$i])
                ));
                $this->get(self::HOST . $v);
                if (preg_match(
                    '#<div id="Desc">([\s\S]+?)</div>#',
                    $this->_response,
                    $v
                )) {
                    $a['description'] = trim(strip_tags(
                        html_entity_decode($v[1], ENT_QUOTES)
                    ));
                } else {
                    $a['description'] = '';
                }
                if (preg_match(
                    '#mediaURL=(http%3A%2F%2F.+?)&post#',
                    $this->_response,
                    $v
                )) {
                    $a['url'] = urldecode(html_entity_decode($v[1], ENT_QUOTES));
                    $a['filename'] = urldecode(basename($a['url']));
                    $videos[] = $a;
                }
            }
            return count($videos)
                ? $videos
                : false;
        }

        $this->log('Nothing found',
                   Log_Abstract::LEVEL_ERROR);
        return false;
    }
}
