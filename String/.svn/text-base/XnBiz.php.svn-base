<?php
/**
 * @package String
 */
/**
 * xn_biz JavaScript unique codes decoder.
 * Used by Show and Adorabley projects.
 *
 * @package String
 * @subpackage XnBiz
 */
class String_XnBiz
{
    /**
     * Decodes xn_biz value
     *
     * @param string $page Arbitrary source of the xn_biz script
     * @return string|false
     */
    static public function decode($page)
    {
        if (!preg_match('#<script[^>]?>\s?(.+?);\s?document\.getElementById\("xn_biz"\)\.value=#', $page, $a)) {
            return false;
        }

        preg_match_all("#var ([^=;]+)(?:\='?(.*?)'?)?;#", $a[1], $a);
        if (8 != count($a[2])) {
            return false;
        }

        $a = $a[2];

        $a[5] = (int)floor(strlen($a[1]) / 2);
        for ($a[2], $l = strlen($a[0]); $a[2] < $l; $a[2]++) {
            $a[3] = ord($a[0][$a[2]]);
            $a[6] = $a[1][$a[3] % strlen($a[1])];
            if ('L' == $a[6]) {
                $a[6] = '<<';
            }
            if ('~' == $a[6]) {
                $a[4] .= -(~$a[3]);
            } else {
                eval("\$a[4] .= intval({$a[3]}{$a[6]}{$a[5]});");
            }
        }

        return "{$a[7]}_{$a[4]}";
    }
}
