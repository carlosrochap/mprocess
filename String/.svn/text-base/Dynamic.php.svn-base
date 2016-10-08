<?php
/**
 * @package String
 */

/**
 * Dynamic strings handling class, requires mbstring extension
 *
 * @package String
 * @subpackage Dynamic
 */
class String_Dynamic
{
    /**
     * Dynamic string raw content
     *
     * @var string
     */
    protected $_raw_content = '';

    /**
     * Dynamic string parsed content nodes
     *
     * @var array
     */
    protected $_nodes = array();


    /**
     * Returns raw string content
     *
     * @return string
     */
    public function get_raw_content()
    {
        return $this->_raw_content;
    }

    /**
     * Returns parsed content nodes
     *
     * @return array
     */
    public function get_nodes()
    {
        return $this->_nodes;
    }

    /**
     * Reads the raw string content from file or string
     *
     * @param resource|string $src File resource, file name or raw string
     * @throws String_Dynamic_Exception When source is empty or unreadable
     */
    public function load($src)
    {
        $this->_raw_content = '';
        $this->_nodes = array();

        if (is_resource($src)) {
            rewind($src);
            while (!feof($src)) {
                $this->_raw_content .= fread($src, 8192);
            }
        } else if (is_file($src) && is_readable($src)) {
            $this->_raw_content = trim(file_get_contents($src));
        } else {
            $this->_raw_content = trim($src);
        }

        if (!$this->_raw_content) {
            throw new String_Dynamic_Exception(
                'Source is empty or unreadable',
                String_Dynamic_Exception::INVALID_ARGUMENT
            );
        }

        $root = new String_Dynamic_Node(str_replace('’', "'", $this->_raw_content));
        $this->_nodes = $root->get_content();

        return $this;
    }

    /**
     * Constructs dynamic string object, reading the raw content
     * from the source specified
     *
     * @param resource|string $src Either file resource or file name or the
     *                             raw content itself
     * @uses ::load() To do all the dirty work
     */
    public function __construct($src=null)
    {
        if ($src) {
            $this->load($src);
        }
    }

    /**
     * Composes a string
     *
     * @return string
     */
    public function get()
    {
        $nodes = $this->_nodes;
        if (is_array($nodes) && count($nodes)) {
            foreach ($nodes as $k => $node) {
                if (is_object($node)) {
                    $nodes[$k] = $node->get();
                }
            }
            return str_replace(
                array("\r", '\[', '\]', '\|'),
                array('', '[', ']', '|'),
                implode('', $nodes)
            );
        } else {
            return (string)$nodes;
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
