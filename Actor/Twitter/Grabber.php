<?php
/**
 * @package Actor
 */

/**
 * @package Actor
 * @subpackage Twitter
 */
class Actor_Twitter_Grabber
    extends Actor_Twitter
    implements Actor_Interface_Grabber
{
    /**
     * Cache of last followers pages
     *
     * @var array
     */
    protected $_followers_pages = array();


    /**
     * Extracts followers from arbitrary page
     *
     * @param string $src
     * @param mixed  $mutual       Extract only mutual followers
     * @param bool   $with_userpic Extract only followers with userpics
     * @return array|false Hash table (twid => user ID)
     */
    protected function _extract_followers($src=null, $mutual=null, $with_userpic=true)
    {
        if (null === $src) {
            $src = $this->_response;
        }

        if (false === strpos($src, 'id="follow_grid"')) {
            return false;
        }

        if (!preg_match_all(
            '#<tr id="user_(?P<twid>\d+)"[^>]+>(?P<content>[\s\S]+?)</tr>#',
            $src,
            $m,
            PREG_SET_ORDER
        )) {
            $this->log('No followers found',
                       Log_Abstract::LEVEL_ERROR);
            return array();
        }

        $followers = array();
        foreach ($m as &$follower) {
            if (false !== strpos(
                $follower['content'],
                'tweets are protected'
            )) {
                continue;
            }
            // See $this->get_followers() method comment on $mutual flag handling
            if (
                is_bool($mutual) &&
                ($mutual != (false !== strpos(
                    $follower['content'],
                    '<strong>Following</strong>'
                )))
            ) {
                continue;
            }
            if (
                is_bool($with_userpic) &&
                ($with_userpic != (false === strpos(
                    $follower['content'],
                    'images/default_profile'
                )))
            ) {
                continue;
            }
            if (preg_match(
                '#"label screenname"><a[^>]+title="([^"]+)#',
                $follower['content'],
                $follower['content']
            )) {
                $followers[(int)$follower['twid']] = trim(html_entity_decode(
                    $follower['content'][1],
                    ENT_QUOTES
                ));
            }
        }
        return $followers;
    }


    /**
     * @see Actor_Twitter::init()
     */
    public function init()
    {
        $this->_followers_pages = array();

        return parent::init();
    }

    /**
     * Fetches followers
     *
     * @param string $user_id      Optional user ID
     * @param string $page         Optional followers' page ID
     * @param mixed  $mutual       Fetch only mutual followers (true),
     *                             only non-mutual (false) or everyone
     * @param bool   $with_userpic Whether to fetch only those with
     *                             userpics, or those without, or
     *                             everyone
     * @return array|false False if invalid followers page
     */
    public function get_followers($user_id=null, $page=null, $mutual=null, $with_userpic=true)
    {
        if (null === $user_id) {
            $user_id = $this->get_user_id();
        }
        if (!$user_id) {
            return false;
        }

        if (null === $page) {
            $page = @$this->_followers_pages[$user_id];
        }

        $this->log("Fetching {$user_id} followers" . ($page
            ? ", page {$page}"
            : ''));

        $this->get(self::HOST . "/{$user_id}/followers", ($page
            ? array('page' => $page)
            : null));
        $this->_dump("followers.{$user_id}.{$page}.html");

        $this->_followers_pages[$user_id] = preg_match(
            '#href="[^"]+page=(\d+)"[^>]+rel="me next"#',
            $this->_response,
            $m
        )
            ? $m[1]
            : null;

        return $this->_extract_followers(null, $mutual, $with_userpic);
    }

    public function set_last_page($user_id, $page)
    {
        $this->_followers_pages[$user_id] = $page;
        return $this;
    }

    public function get_last_page($user_id=null)
    {
        if (null === $user_id) {
            $user_id = $this->get_user_id();
        }
        return isset($this->_followers_pages[$user_id])
            ? $this->_followers_pages[$user_id]
            : null;
    }
}
