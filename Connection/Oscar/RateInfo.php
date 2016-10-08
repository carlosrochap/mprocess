<?php
/**
 * @package Connection
 */
/**
 * OSCAR (TM) connection rate limits info
 *
 * @package Connection
 * @subpackage Oscar
 */
class Connection_Oscar_RateInfo
{
    /**
     * Rate limits classes
     *
     * @var array
     */
    protected $_classes = array();

    /**
     * Rate limits groups
     *
     * @var array
     */
    protected $_groups = array();


    /**
     * Initializes the rate limits info object with supplied data
     *
     * @param string $data Raw rate limits info data
     */
    public function __construct($data)
    {
        $this->load($data);
    }

    /**
     * Loads rate limits from raw data
     *
     * @param string $data
     */
    public function load($data)
    {
        $this->_classes = array();

        $a = unpack('nnum', $data);
        $data = substr($data, 2);
        $classes_num = $a['num'];
        $fields = implode('/', array('nid',
                                     'Nwindow_size',
                                     'Nclear_level',
                                     'Nalert_level',
                                     'Nlimit_level',
                                     'Ndisconnect_level',
                                     'Ncurrent_level',
                                     'Nmax_level',
                                     'Nlast_time',
                                     'Ccurrent_state'));
        while ($classes_num--) {
            $class = unpack($fields, $data);
            $data = substr($data, 35);
            $this->_classes[$class['id']] = $class;
        }

        $this->_groups = array();

        while ($data) {
            $a = unpack('nid/nnum', $data);
            $data = substr($data, 4);
            list($id, $pairs_num) = array($a['id'], $a['num']);
            $this->_groups[$id] = array();
            for (; $data && $pairs_num; $pairs_num--) {
                $this->_groups[$id][] = unpack('nfamily/nsubtype', $data);
                $data = substr($data, 4);
            }
        }

        return $this;
    }

    /**
     * Returns rate limits classes
     *
     * @return array
     */
    public function get_classes()
    {
        return $this->_classes;
    }

    /**
     * Returns a rate limits class
     *
     * @param int $id Class ID
     * @return mixed False if not found
     */
    public function get_class($id)
    {
        return isset($this->_classes[$id])
            ? $this->_classes[$id]
            : false;
    }

    /**
     * Returns rate limits groups
     *
     * @return array
     */
    public function get_groups()
    {
        return $this->_groups;
    }

    /**
     * Returns rate limits group
     *
     * @param int $id
     * @return mixed False if not found
     */
    public function get_group($id)
    {
        return isset($this->_groups[$id])
            ? $this->_groups[$id]
            : false;
    }
}
