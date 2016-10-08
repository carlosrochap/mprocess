<?php
/**
 * @package Pool
 */

/**
 * Profile details' pool
 *
 * @package Pool
 * @subpackage Db
 */
class Pool_UserInfo extends Pool_Db_Abstract
{
    const DEFAULT_COUNTRY = 'US';


    static protected $_invalid_regions = array(
        'US' => array(
            'American Samoa',
            'Armed Forces Americas',
            'Armed Forces Europe',
            'Armed Forces Pacific',
            'Federated States of Micronesia',
            'Guam',
            'Hawaii',
            'Marshall Islands',
            'Northern Marianna Islands',
            'Palau',
            'Puerto Rico',
            'U.S. Virgin Islands',
        ),
    );


    protected $_stmts = array(
        // Fill a pool of random FEMALE profile IDs
        'fill_profile_id' => array('db' => 'globo07', 'query' => '
            SELECT `id` AS `profile`
            FROM `username`
            WHERE `sex` = "female"
            ORDER BY RAND()
            LIMIT ?
        '),
        // Fill a pool of profiles
        'fill_profile' => array('db' => 'globo07', 'query' => '
            SELECT `id` AS `profile`,
                   `username`,
                   UPPER(SUBSTRING(`sex`, 1, 1)) AS `gender`,
                   `email`,
                   `pass`
            FROM `username`
            WHERE `sex` = "female"
            ORDER BY RAND()
            LIMIT ?
        '),
        'fill_username' => array('db' => 'globo07', 'query' => '
            SELECT `username`
            FROM `username`
            WHERE `sex` = "female"
            ORDER BY RAND()
            LIMIT ?
        '),
        'fill_email_from_username' => array('db' => 'globo07', 'query' => '
            SELECT `email`,
                   `pass` AS `email_pass`
            FROM `username`
            WHERE `failed` = "N" AND
                  `email` LIKE "%@yahoo.com"
            ORDER BY RAND()
            LIMIT ?
        '),
        'fill_email_from_yahoo_id' => array('db' => 'globo07', 'query' => '
            SELECT `username` AS `email`,
                   `password` AS `email_pass`
            FROM `yahoo_id`
            WHERE `failed` = "N" AND
                  `password` IS NOT NULL
            ORDER BY RAND()
            LIMIT ?
        '),
        'fill_email_from_ymail' => array('db' => 'ymail', 'query' => '
            SELECT `a`.`email`,
                   `a`.`pass` AS `email_pass`
            FROM `accounts` AS `a`
            LEFT OUTER JOIN `projects_accounts` AS `pa` ON
                 (`pa`.`project` = LOWER(?) AND `pa`.`account` = `a`.`id`)
            WHERE `a`.`is_suspended` = 0 AND
                  `pa`.`account` IS NULL
            ORDER BY RAND()
            LIMIT ?
        '),
        'fill_email_from_mmm' => array('db' => 'mmm', 'query' => '
            SELECT `a`.`user_id` AS `email`,
                   `a`.`pass` AS `email_pass`
            FROM `accounts` AS `a`
            LEFT OUTER JOIN `projects_accounts` AS `pa` ON
                 (`pa`.`project` = LOWER(?) AND `pa`.`account` = `a`.`profile`)
            WHERE `a`.`is_confirmed` = 1 AND
                  `a`.`is_suspended` = 0 AND
                  `pa`.`account` IS NULL
            ORDER BY RAND()
            LIMIT ?
        '),
        // Fetch profile id by e-mail
        'get_profile_id' => array('db' => 'globo07', 'query' => '
            SELECT `id`
            FROM `username`
            WHERE `email` LIKE ? OR
                  `username` LIKE ?
        '),
        // Fetch profile details
        'get_profile_details' => array('db' => 'globo07', 'query' => '
            SELECT `id` AS `profile`,
                   `username`,
                   LOWER(`email`) AS `email`,
                   `pass` AS `email_pass`,
                   `welcome_msg` AS `about_me`,
                   `picture_location` AS `userpic`,
                   `city` AS `locality`,
                   `state` AS `region`,
                   `country`,
                   `zip_code` AS `postal_code`,
                   UPPER(SUBSTRING(`sex`, 1, 1)) AS `gender`,
                   CONCAT_WS("-", `year_of_birth`, `month_of_birth`, `day_of_birth`) AS `birthday`
            FROM `username`
            WHERE `id` = ?
        '),
        // Fetch a location by country
        'get_locations' => array('db' => 'globo07', 'query' => '
            SELECT `il`.`city` AS `locality`,
                   IFNULL(`lr`.`region`, `il`.`region`) AS `region`,
                   `il`.`postalCode` AS `postal_code`,
                   `il`.`country`
            FROM `geoip`.`ip_location` AS `il`
            LEFT JOIN `geoip`.`location_regions` AS `lr` ON
                 (`lr`.`country_code` = `il`.`country` AND
                  `lr`.`region_code` = `il`.`region`)
            WHERE `il`.`country` = ?
            ORDER BY RAND()
            LIMIT ?
        '),
        'add_used_email_ymail' => array('db' => 'ymail', 'query' => '
            INSERT INTO `projects_accounts`
                   (`project`, `account`)
            SELECT LOWER(?), `id`
            FROM `accounts`
            WHERE `email` LIKE ?
        '),
        'add_used_email_mmm' => array('db' => 'mmm', 'query' => '
            INSERT INTO `projects_accounts`
                   (`project`, `account`)
            SELECT LOWER(?), `profile`
            FROM `accounts`
            WHERE `user_id` LIKE ?
        '),
    );


    protected $_email_src = array('username', 'yahoo_id');


    /**
     * Fills random e-mails pool from either globo07/gamma.username or
     * globo07/gamma.yahoo_id
     *
     * @param int $size
     * @return array|false
     */
    protected function _fill_email($size)
    {
        return call_user_func(array(
            $this,
            'fill_email_from_' . $this->_email_src[array_rand($this->_email_src)]
        ), $size, $this->_config['project']['name']);
    }


    /**
     * Fetches random profile details
     *
     * @see Pool_Abstract::get()
     */
    public function get($name=null)
    {
        $item = parent::get($name);
        if (is_array($item) && isset($item['email'])) {
            $item['email'] = strtolower($item['email']);
        }
        return $item;
    }

    /**
     * Fetches profile ID by email or username
     *
     * @param string $user_id E-mail or username
     * @return int|false
     */
    public function get_profile_id($user_id)
    {
        $m = 'get_local_profile_id';
        if (method_exists($this, $m)) {
            $v = $this->{$m}($user_id);
            if ($v) {
                $user_id = $v;
            }
        } else if (!empty($this->_stmts[$m])) {
            $a = $this->_execute($m, $user_id);
            if ($a) {
                $user_id = $a[0];
                if (is_numeric($user_id)) {
                    return $user_id;
                }
            }
        }
        $a = $this->_execute('get_profile_id', $user_id, $user_id);
        return $a
            ? $a[0]
            : false;
    }

    /**
     * Fetches profile details
     *
     * @param string|int $profile Optional profile ID or e-mail
     * @return array|false
     */
    public function get_profile_details($profile=null)
    {
        if (null === $profile) {
            $profile = $this->get('profile_id');
        } else if (!is_int($profile) && !is_numeric($profile)) {
            $id = $this->get_profile_id($profile);
            if ($id) {
                $profile = $id;
            } else {
                return false;
            }
        }

        $a = $this->_execute('get_profile_details', $profile);
        if ($a) {
            $a = $a[0];
            $k = 'birthday';
            $a[$k] = array_map('intval', explode('-', $a[$k], 3));
            if (!$a[$k][0]) {
                $a[$k] = Pool_Generator::generate_birthday();
            }
            return $a;
        }

        return false;
    }

    /**
     * Fetches profile userpic
     *
     * @param string|int $profile Either profile ID or e-mail
     * @return string|false
     */
    public function get_profile_userpic($profile)
    {
        $a = $this->get_profile_details($profile);
        return $a
            ? $a['userpic']
            : false;
    }

    public function get_location($country=self::DEFAULT_COUNTRY)
    {
        $pool = &$this->_pools["location_{$country}"];
        if (empty($pool)) {
            $pool = $this->_execute('get_locations', $country, $this->_size);
            if (empty($pool)) {
                return false;
            }
        }
        $idx = array_rand($pool);
        $location = $pool[$idx];
        unset($pool[$idx]);

        switch ($country) {
        case 'US':
            if (!$location['postal_code'] || !$location['region'] || (
                isset(self::$_invalid_regions[$country]) &&
                in_array($location['region'], self::$_invalid_regions[$country])
            )) {
                return $this->get_location($country);
            }
            break;
        }

        return $location;
    }

    public function fill_email_from_username($size)
    {
        return $this->_execute('fill_email_from_username', $size);
    }

    public function fill_email_from_yahoo_id($size)
    {
        return $this->_execute('fill_email_from_yahoo_id', $size);
    }

    public function fill_email_from_ymail($size, $project)
    {
        return $this->_execute('fill_email_from_ymail', $project, $size);
    }

    public function fill_email_from_mmm($size, $project)
    {
        return $this->_execute('fill_email_from_mmm', $project, $size);
    }

    public function add_used_email($email)
    {
        $project = strtolower($this->_config['project']['name']);
        foreach (array_intersect($this->_email_src, array('ymail', 'mmm')) as $k) {
            try {
                if ($this->_execute("add_used_email_{$k}", $project, $email)) {
                    return true;
                }
            } catch (Pool_Db_Exception $e) {
                $this->log($e, Log_Abstract::LEVEL_DEBUG);
            }
        }
        return false;
    }
}
