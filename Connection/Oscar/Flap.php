<?php
/**
 * @package Connection
 */
/**
 * Oscar (TM) FLAP encoder/decoder
 *
 * @package Connection
 * @subpackage Oscar
 */
class Connection_Oscar_Flap
{
    /**
     * FLAP marker, currently 0x2a
     */
    const MARKER = 0x2a;

    /**
     * Known FLAP channels
     */
    const CHANNEL_OPEN       = 0x01; // Auth and connection init
    const CHANNEL_SNAC       = 0x02; // SNAC commands & data
    const CHANNEL_ERROR      = 0x03; // Error responses
    const CHANNEL_CLOSE      = 0x04; // Closing connection
    const CHANNEL_KEEP_ALIVE = 0x05; // Keep-alive ping


    /**
     * Packs arbitrary data into FLAP packet, increases sequence number
     *
     * @param mixed $data
     * @param int   $channel FLAP channel to use
     * @param int   $seqnum  FLAC sequence number, updated in-place
     * @return string Raw binary FLAP content
     */
    static public function encode($data, $channel, &$seqnum)
    {
        $seqnum = ($seqnum + 1) % 0x7fff;

        return pack('CCnn', self::MARKER, $channel, $seqnum, strlen($data)) .
               $data;
    }

    /**
     * Decodes raw FLAP packet with optional length check
     *
     * @param sting $flap
     * @param bool  $do_length_check
     * @return array Hash table with FLAP details
     * @throws Connection_Exception When FLAP packet is invalid or
     *                              shorter than expected
     */
    static public function decode($flap, $do_length_check=true)
    {
        $a = unpack('Cmarker/Cchannel/nseqnum/nlength', $flap);

        if (self::MARKER != $a['marker']) {
            throw new Connection_Exception(
                'Malformed FLAP packet: invalid marker',
                Connection_Exception::INVALID_ARGUMENT
            );
        }

        if ($do_length_check &&
            ((6 + $a['length']) > strlen($flap))) {
            throw new Connection_Exception(
                'Malformed FLAP packet: invalid data length',
                Connection_Exception::INVALID_ARGUMENT
            );
        }

        return array(
            'channel' => $a['channel'],
            'length'  => $a['length'],
            'data'    => ($a['length'] ? substr($flap, 6, $a['length'])
                                       : null)
        );
    }
}
