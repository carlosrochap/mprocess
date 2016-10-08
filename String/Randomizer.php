<?php

class String_Randomizer
{
    const LETTERS_TO_SKIP = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ[]-_';


    protected $_randomizers = array();


    static public function talk_31334($s)
    {
        $map = array(
            'o' => '0',
            'i' => '1',
            'l' => '1',
            'f' => '4',
            'e' => '3',
            'b' => '6',
            'g' => '9',
        );
        $tr = array('', '');
        for ($i = rand(1, 3); $i; $i--) {
            $idx = array_rand($map);
            $tr[$idx] = $map[$idx];
        }
        return strtr(str_replace(
            array(' to ', ' for '),
            array(' 2 ', ' 4 '),
            $s
        ), $tr);
    }

    static public function replace_letters($s, $chars='abcdefghijklmnopqrstuvwxyz0123456789')
    {
        $a = mb_split('\s+', $s);
        for ($i = count($a) - 1; 0 <= $i; $i--) {
            if (!rand(0, 3)) {
                $idx = rand(0, mb_strlen($a[$i]) - 1);
                if (false === mb_strpos(
                    self::LETTERS_TO_SKIP,
                    mb_substr($a[$i], $idx, 1)
                )) {
                    $a[$i] = mb_substr($a[$i], 0, $idx) . mb_substr(
                        $chars,
                        rand(0, mb_strlen($chars) - 1)
                    ) . mb_substr($a[$i], $idx + 1);
                }
            }
        }
        return implode(' ', $a);
    }

    static public function misplace_letters($s, $min=1, $max=3)
    {
        for ($idx = 0, $l = mb_strlen($s), $i = rand($min, $max); $l && $i; $i--) {
            do {
                $idx = rand(1, $l - 1);
                $cl = mb_substr($s, $idx - 1, 1);
                $cr = mb_substr($s, $idx, 1);
            } while (
                (false !== mb_strpos(self::LETTERS_TO_SKIP, $cl)) ||
                (false !== mb_strpos(self::LETTERS_TO_SKIP, $cr))
            );
            $s = implode('', array(
                mb_substr($s, 0, $idx - 1),
                $cr,
                $cl,
                mb_substr($s, $idx + 1)
            ));
        }
        unset($idx, $l, $i);
        return $s;
    }

    static public function drop_letters($s, $min=1, $max=3)
    {
        for ($idx = 0, $l = mb_strlen($s), $i = rand($min, $max); $l && $i; $i--) {
            do {
                $idx = rand(0, $l - 1);
                $c = mb_substr($s, $idx, 1);
            } while (!$c || (false !== mb_strpos(self::LETTERS_TO_SKIP, $c)));
            $s = implode('', array(
                mb_substr($s, 0, $idx),
                mb_substr($s, $idx + 1)
            ));
        }
        unset($idx, $l, $i);
        return $s;
    }

    static public function encode_html_entities($s, $min=1, $max=3)
    {
        $chars = array(';', '#', '&', ' ');
        for ($c = '', $l = strlen($s), $i = rand($min, $max); $l && $i; $i--) {
            do {
                $c = $s[rand(0, $l - 1)];
            } while (is_numeric($c) || (false !== array_search($c, $chars)));
            $s = str_replace($c, '&#' . ord($c) . ';', $s);
            $chars[] = $c;
        }
        unset($chars, $c, $l, $i);
        return $s;
    }

    static public function interleave($s, $chars='*^<>')
    {
        for ($l = mb_strlen($s), $i = rand(intval($l / 5), intval($l / 3)); $i; $i--) {
            $idx = rand(1, $l - 1);
            $s =
                mb_substr($s, 0, $idx) .
                $chars[rand(0, strlen($chars) - 1)] .
                mb_substr($s, $idx);
        }
        return $s;
    }


    public function init()
    {
        $this->_randomizers = array();
    }

    public function remove($randomizer)
    {
        foreach ($this->_randomizers as $k => &$v) {
            if ($randomizer == $v[0]) {
                unset($this->_randomizers[$k]);
            }
        }
        return $this;
    }

    public function add($randomizer)
    {
        $this->remove($randomizer);
        $this->_randomizers[] = func_get_args();
        return $this;
    }

    public function randomize($s)
    {
        foreach ($this->_randomizers as &$randomizer) {
            $method = @$randomizer[0];
            if (method_exists($this, $method)) {
                $s = call_user_func_array(
                    array($this, $method),
                    array_merge(array($s), array_slice($randomizer, 1))
                );
            }
        }
        return $s;
    }
}
