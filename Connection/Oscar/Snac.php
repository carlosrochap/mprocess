<?php
/**
 * @package Connection
 */
/**
 * OSCAR (TM) SNAC encoder/decoder.
 *
 * @package Connection
 * @subpackage Oscar
 */
class Connection_Oscar_Snac
{
    /**
     * Known SNAC families
     */
    const FAMILY_CONTROL = 0x01;
    const FAMILY_AUTH    = 0x17;

    /**
     * Families versions as sent by servers on Oct 11, 2003
     *
     * @var array
     */
    protected $_family_versions = array(0x01 => 4,
                                        0x02 => 1,
                                        0x03 => 1,
                                        0x04 => 1,
                                        0x06 => 1,
                                        0x08 => 1,
                                        0x09 => 1,
                                        0x0a => 1,
                                        0x0b => 1,
                                        0x0c => 1,
                                        0x13 => 4,
                                        0x15 => 1,
                                        0x18 => 1);


    /**
     * Packs arbitrary data into SNAC packet
     *
     * @param mixed $data
     * @param int   $family  SNAC family
     * @param int   $subtype SNAC family subtype
     * @param int   $flags   Optional SNAC flags
     * @param int   $reqid   Optional SNAC request ID
     * @return string Raw SNAP packet
     */
    static public function encode($data, $family, $subtype, $flags=0, $reqid=0)
    {
        return pack('nnnN', $family, $subtype, $flags, $reqid) . $data;
    }

    /**
     * Decodes raw SNAC packet
     *
     * @param string $snac
     * @return array Hash of SNAC packet details
     */
    static public function decode($snac)
    {
        $a = unpack('nfamily/nsubtype/nflags/Nreqid', $snac);
        $a['data'] = (10 < strlen($snac))
            ? substr($snac, 10)
            : null;
        return $a;
    }


    /**
     * Returns versions for supplied SNAC families, or all known families
     * version if none specified
     *
     * @param int|array $family SNAC family of a list of families
     * @return array
     */
    public function get_family_versions($family=null)
    {
        if ($family && !is_array($family)) {
            $family = array($family);
        }

        if (empty($family)) {
            return $this->_family_versions;
        }

        $a = array();
        foreach ($family as $v) {
            if (isset($this->_family_versions[$v])) {
                $a[$v] = $this->_family_versions[$v];
            }
        }
        return $a;
    }
}
