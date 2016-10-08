<?php

abstract class Xml_Document
{
    static public function get($src)
    {
        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = false;
        $m = (false !== stripos($src, '<html')) ? 'loadHTML' : 'loadXML';
        return @$doc->{$m}($src) ? $doc : null;
    }

    static public function find($doc, $exp)
    {
        if (!$doc instanceof DOMDocument) {
            if (!$doc = self::get($doc)) {
                return null;
            }
        }
        if ('/' != $exp[0]) {
            $exp = "//{$exp}";
        }

        $xpath = new DOMXPath($doc);
        $nodes = $xpath->query($exp, $doc->documentElement);
        if ($nodes->length) {
            $result = array();
            foreach ($nodes as $node) {
                $result[] = $node;
            }
            return $result;
        }

        return null;
    }

    static public function find_first($doc, $exp)
    {
        return ($nodes = self::find($doc, "{$exp}[1]")) ? $nodes[0] : null;
    }
}
