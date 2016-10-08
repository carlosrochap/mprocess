<?php
/**
 * @package Connection
 */
/**
 * OSCAR (TM) TLV encoder/decoder
 *
 * @package Connection
 * @subpackage Oscar
 */
class Connection_Oscar_Tlv
{
    /**
     * Constructs a TLV tuple
     *
     * @param int|array $type  Data type or hash of types and values
     * @param string    $value
     * @return string Raw TLV tuple
     */
    static public function encode($type, $value=null)
    {
        if (!is_array($type)) {
            $type = array($type => $value);
        }

        $tlv = array();
        foreach ($type as $t => $v) {
            $tlv[] = pack('nn', $t, strlen($v)) . $v;
        }
        return implode('', $tlv);
    }

    /**
     * Decodes a raw TLV string into native PHP data types
     *
     * @param string $tlv
     * @return array TLVs as hash
     * @throws Connection_Exception On invalid TLV data length
     */
    static public function decode($tlv)
    {
        $tlvs = array();

        while ($tlv) {
            $a = unpack('ntype/nlength', $tlv);

            if ((4 + $a['length']) > strlen($tlv)) {
                throw new Connection_Exception(
                    'Malformed TLV tuple: invalid data length',
                    Connection_Exception::INVALID_ARGUMENT
                );
            }

            $tlvs[$a['type']] = $a['length']
                ? substr($tlv, 4, $a['length'])
                : false;

            $tlv = substr($tlv, 4 + $a['length']);
        }

        return $tlvs;
    }
}
