<?php
/**
 * @package Actor
 */

/**
 * @package Actor
 * @subpackage Break
 */
class Actor_Break_Grabber
    extends Actor_Break
    implements Actor_Interface_Grabber
{
    const TOP_RATED_URL   = '/videos/newest/%d';
    const MOST_VIEWED_URL = '/videos/most-viewed-daily/%d';
    const FLV_FILE_URL    = 'http://video1.break.com/dnet/media/%s.flv';


    protected $_page = 0;


    /**
     * Extracts videos details from list
     *
     * @param string $src Optional list content
     * @return array|false
     */
    protected function _extract_videos($src=null)
    {
        $this->log('Extracting video clips');

        if (null === $src) {
            $src = &$this->_response;
        }

        if (!preg_match_all(
            '#<div class="cat-cnt-item-th">(.+?)</div>#',
            $src,
            $m
        )) {
            return false;
        }

        $videos = array();
        foreach ($m[1] as &$s) {
            if (!preg_match(
                '#href="(?P<url>[^"]+)" title="(?P<desc>[^"]+)#',
                $s,
                $a
            )) {
                continue;
            }

            preg_match('#<span>(?P<title>[^<]+)</span>#', $s, $b);
            if (!preg_match(
                '#src="[^"]+/media/(?P<filename>[^"]+)_\d{4}_thumb\.jpg#',
                $s,
                $c
            )) {
                $resp = $this->_connection->get($a['url']);
                if (preg_match(
                    '#sGlobalFileName=\'([^\']+)\'#',
                    $resp,
                    $c1
                ) && preg_match(
                    '#sGlobalContentFilePath=\'([^\']+)\'#',
                    $resp,
                    $c2
                )) {
                    $c = array('filename' => "{$c2[1]}/{$c1[1]}");
                } else {
                    $c = null;
                }
            }

            if (!empty($c['filename'])) {
                $c['url'] = sprintf(self::FLV_FILE_URL, $c['filename']);
                $videos[] = array_combine(
                    array('url', 'filename', 'title', 'description'),
                    array_map('trim', array_map(
                        'html_entity_decode',
                        array($c['url'], $c['filename'], $b['title'], $a['desc'])
                    ))
                );
            }
        }
        return $videos;
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

    /**
     * Parses out a list of videos from specific URL
     *
     * @param string $url
     * @param int    $page Optional page to parse, zero-based
     * @return array|false
     */
    public function grab($url=self::MOST_VIEWED_URL, $page=null)
    {
        if (null !== $page) {
            $this->page = $page;
        }

        $this->log("Parsing page {$this->_page}");

        $this->get(self::HOST . sprintf($url, $this->_page + 1));
        $this->_dump("videos.{$this->_page}.html");
        $videos = $this->_extract_videos();
        if ($videos) {
            $this->_page++;
            return $videos;
        }

        $this->log('Nothing found',
                   Log_Abstract::LEVEL_ERROR);
        return false;
    }

    /**
     * Returns a page of most viewed videos
     *
     * @param int $page
     * @return array|false
     */
    public function grab_most_viewed($page=null)
    {
        return $this->grab(self::MOST_VIEWED_URL, $page);
    }

    /**
     * Returns a page of top rated videos
     *
     * @param int $page
     * @return array|false
     */
    public function grab_top_rated($page=null)
    {
        return $this->grab(self::TOP_RATED_URL, $page);
    }
}
