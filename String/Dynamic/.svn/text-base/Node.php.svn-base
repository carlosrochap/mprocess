<?php
/**
 * @package String
 */

/**
 * Dynamic string's node
 *
 * @package String
 * @subpackage Dynamic
 */
class String_Dynamic_Node
{
    /**
     * Node content
     *
     * @var string|array
     */
    protected $_content = array();


    /**
     * Parses a node from arbitrary source
     *
     * @param string $src
     * @return array
     */
    static public function parse($src)
    {
        $nodes = array('');

        $prev_char = $curr_char = '';
        $curr_pos = $brackets_count = $nodes_count = 0;
        $src_len = mb_strlen($src);
        while ($curr_pos < $src_len) {
            $curr_char = mb_substr($src, $curr_pos, 1);

            if ('\\' != $prev_char) {
                if ('[' == $curr_char) {
                    if (!$brackets_count) {
                        $nodes[] = '';
                        $nodes_count++;
                        $curr_char = '';
                    }
                    $brackets_count++;
                } else if (']' == $curr_char) {
                    $brackets_count--;
                    if (!$brackets_count) {
                        $nodes[$nodes_count] = new String_Dynamic_Node($nodes[$nodes_count]);
                        $nodes[] = '';
                        $nodes_count++;
                        $curr_char = '';
                    }
                } else if (('|' == $curr_char) && !$brackets_count) {
                    $nodes[] = '';
                    $nodes_count++;
                    $curr_char = '';
                }
            }

            $prev_char = $curr_char;
            $nodes[$nodes_count] .= $curr_char;

            $curr_pos++;
        }

        return $nodes;
    }


    /**
     * Instantiates a dynamic string node
     *
     * @param string $content Node content
     */
    public function __construct($content=null)
    {
        $this->_content = array();
        if ($content) {
            $nodes = $this->parse($content);
            $this->_content = (1 < count($nodes))
                ? $nodes
                : $nodes[0];
        }
    }

    /**
     * Returns current content
     *
     * @return array
     */
    public function get_content()
    {
        return $this->_content;
    }

    /**
     * Generates and returns a dynamic substring
     *
     * @return string
     */
    public function get()
    {
        if (is_string($this->_content)) {
            return $this->_content;
        } else if (empty($this->_content)) {
            return '';
        } else {
            $node = $this->_content[array_rand($this->_content)];
            return is_object($node)
                ? $node->get()
                : $node;
        }
    }

    /**
     * Alias for {@link ::get()}
     */
    public function __toString()
    {
        return $this->get();
    }
}
