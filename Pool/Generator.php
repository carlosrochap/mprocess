<?php
/**
 * @package Pool
 */

/**
 * Misc generators
 *
 * @package Pool
 */
class Pool_Generator extends Pool_Abstract
{
    static public $zodiac_signs = array(
        'Aquarius'    => '01-20',
        'Pisces'      => '02-19',
        'Aries'       => '03-21',
        'Taurus'      => '04-20',
        'Gemini'      => '05-21',
        'Cancer'      => '06-21',
        'Leo'         => '07-23',
        'Virgo'       => '08-23',
        'Libra'       => '09-23',
        'Scorpio'     => '10-23',
        'Sagittarius' => '11-22',
        'Capricorn'   => '12-22',
    );


    /**
     * Generates random password [A-Za-z\d]{min_len,max_len}
     *
     * @param int $min_len
     * @param int $max_len
     * @return string
     */
    static public function generate_password($min_len=8, $max_len=12)
    {
        for ($s = '', $l = rand($min_len, $max_len); $l; $l--) {
            $s .= chr(($r = rand(0, 2))
                ? rand(0x21, 0x3a) + ($r << 5)
                : rand(0x30, 0x39));
        }
        return $s;
    }

    /**
     * Generates a username based on existing one
     *
     * Returns {@link http://tools.ietf.org/html/rfc5322#section-3.4.1 local-part}
     * if e-mail address provided, otherwise adds a random digit to username;
     * optionally filters the results to [A-Za-z\d]
     *
     * @param string|array $username Username, e-mail address, or profile details
     * @param bool $do_filter Whether to filter non-alphanumeric characters
     *                        from the generated username
     * @param int $max_len Maximal length
     * @param string $separators Allowed separators
     * @return string
     */
    static public function generate_username($username, $do_filter=false, $max_len=0, $separators='_-')
    {
        if (is_array($username)) {
            if (!empty($username['user_id'])) {
                return self::generate_username(
                    $username['user_id'],
                    $do_filter,
                    $max_len
                );
            }

            $a = array(
                &$username['first_name'],
                &$username['last_name'],
                &$username['username'],
            );
            shuffle($a);
            $separators = str_split($separators);
            $separators[] = '';
            return self::generate_username(
                $a[0] . $separators[array_rand($separators)] . $a[1],
                $do_filter,
                $max_len
            );
        }

        $i = mb_strpos($username, '@');
        if (false !== $i) {
            $username = mb_substr($username, 0, $i);
        }
        if ($do_filter) {
            preg_match_all('/[A-Za-z\d]+/', $username, $username);
            $username = implode('', $username[0]);
        }
        return mb_strtolower(((0 < $max_len)
            ? mb_substr($username, 0, $max_len - rand(1, 3))
            : $username) . (string)rand(0, 9));
    }

    /**
     * Generates random birthday date in specific age range
     *
     * Validation is not required since generated day of month does not
     * exceed 27.
     *
     * @param int $min_age
     * @param int $max_age
     * @return array Triple (year, month, day)
     */
    static public function generate_birthday($min_age=21, $max_age=29)
    {
        return array(
            (int)gmdate('Y') - rand($min_age, $max_age),
            rand(1, 12),
            rand(1, 27)
        );
    }

    static function get_zodiac_sign(array $birthday)
    {
        $md = str_pad($birthday[1], 2, '0', STR_PAD_LEFT) . '-' .
              str_pad($birthday[2], 2, '0', STR_PAD_LEFT);
        $sign = 'Capricorn';
        foreach (self::$zodiac_signs as $k => $v) {
            if ($v <= $md) {
                $sign = $k;
            } else {
                break;
            }
        }
        return $sign;
    }


    /**
     * Fills a pool of randomly generated passwords
     *
     * @param int $size
     * @return array
     * @uses ::generate_password()
     */
    protected function _fill_pass($size)
    {
        $passwords = array();
        while ($size--) {
            $passwords[] = $this->generate_password();
        }
        return $passwords;
    }

    /**
     * Alias for @link ::_fill_pass()
     */
    protected function _fill_password($size)
    {
        return $this->_fill_pass($size);
    }

    /**
     * Alias for @link ::_fill_pass()
     */
    protected function _fill_pwd($size)
    {
        return $this->_fill_pass($size);
    }

    /**
     * Fills a pool of randomly generated birthdays
     *
     * @param int $size
     * @return array
     * @uses ::generate_birthday()
     */
    protected function _fill_birthday($size)
    {
        $birthdays = array();
        while ($size--) {
            $birthdays[] = $this->generate_birthday();
        }
        return $birthdays;
    }
}
