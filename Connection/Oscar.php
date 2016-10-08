<?php
/**
 * @package Connection
 */
/**
 * Low-level OSCAR (AIM) connection
 *
 * @package Connection
 * @subpackage Oscar
 */
class Connection_Oscar extends Connection_Abstract
{
    /**
     * Current OSCAR protocol version
     */
    const PROTOCOL_VERSION = 0x01;

    /**
     * Max buffer length for socket_recv()
     */
    const RECV_BUF_LEN = 8192;

    /**
     * Default authorization host & port
     */
    const AUTH_SERVER = 'login.oscar.aol.com:5190';

    /**
     * Salt used when hashing passwords for authorization
     */
    const AUTH_PASS_HASH_SALT = 'AOL Instant Messenger (SM)';


    /**
     * Client details (ID string, versions etc.)
     *
     * @var array
     */
    protected $_client_details = array(
        0x03 => 'AOL Instant Messenger, version 5.1.3036/WIN32',
        0x16 => "\x01\x09",
        0x17 => "\x00\x05",
        0x18 => "\x00\x01",
        0x19 => "\x00\x00",
        0x1a => "\x0b\xdc",
        0x14 => "\x00\x00\x00\xd2",
        0x0f => 'en',
        0x0e => 'us',
        0x4a => "\x01"
    );

    /**
     * Timeout duration (sec)
     *
     * @var int
     */
    protected $_timeout = 30;

    /**
     * Opened socket
     *
     * @var resource
     */
    protected $_socket = null;

    /**
     * FLAP sequence number, max value 0x7fff
     *
     * Updated automatically by {@link Connection_Oscar_Flap::encode()},
     * manual handling is strongly discouraged.
     *
     * @var int
     */
    protected $_seqnum = 0x0000;

    /**
     * Auth cookie
     *
     * @var string
     */
    protected $_cookie = '';

    /**
     * BOS server host & port
     *
     * @var string
     */
    protected $_bos = '';

    /**
     * BOS servers's available families list
     *
     * @var array
     */
    protected $_bos_families = array();


    /**
     * Hashes passwords using session key with {@link ::AUTH_PASS_HASH_SALT salt}
     *
     * @param string $pass
     * @param string $key
     * @return string Raw binary hash value
     */
    static public function get_pass_hash($pass, $key)
    {
        return md5($key . $pass . self::AUTH_PASS_HASH_SALT, true);
    }


    /**
     * Initializes the connection, resets FLAP sequence number
     */
    public function init()
    {
        $this->_seqnum = rand(0, 0x3fff);

        return $this;
    }

    /**
     * Closes opened socket, cleans up
     */
    public function close()
    {
        if ($this->_socket) {
            $this->_send(null, Connection_Oscar_Flap::CHANNEL_CLOSE);
            socket_close($this->_socket);
        }
        return $this;
    }

    /**
     * Sets timeout duration
     *
     * @param int $timeout
     */
    public function set_timeout($timeout)
    {
        $this->_timeout = $timeout;
        return $this;
    }

    /**
     * Returns current timeout duration
     *
     * @return int
     */
    public function get_timeout()
    {
        return $this->_timeout;
    }

    /**
     * Returns current auth cookie
     *
     * @return string
     */
    public function get_cookie()
    {
        return $this->_cookie;
    }

    /**
     * Returns current BOS
     *
     * @return string
     */
    public function get_bos()
    {
        return $this->_bos;
    }

    /**
     * Wraps in FLAP and sends out arbitrary data
     *
     * @param string $data    Data binary string
     * @param int    $channel Channel number, defaults to SNAC data channel
     * @return bool
     * @uses Connection_Oscar_Flap::encode() To wrap the data into FLAP
     * @throws Connection_Exception When no connection found
     */
    protected function _send($data, $channel=Connection_Oscar_Flap::CHANNEL_SNAC)
    {
        if (!is_resource($this->_socket)) {
            throw new Connection_Exception(
                'No connections opened',
                Connection_Exception::IO_ERROR
            );
        }

        $flap = Connection_Oscar_Flap::encode($data, $channel, $this->_seqnum);

        if ($this->is_verbose) {
            echo "SEND: {$flap}\n";
        }

        $l = strlen($flap);
        return ($l == socket_send($this->_socket, $flap, $l, 0));
    }

    /**
     * Sends out SNAC packet
     *
     * @see Connection_Oscar_Snac::encode() for arguments details
     * @return bool
     */
    protected function _send_snac($data, $family, $subtype, $flags=0, $reqid=0)
    {
        $snac = Connection_Oscar_Snac::encode($data,
                                              $family,
                                              $subtype,
                                              $flags,
                                              $reqid);
        return $this->_send($snac, Connection_Oscar_Flap::CHANNEL_SNAC);
    }

    /**
     * Reads a FLAP response from current socket connection
     *
     * @return array|false (channel, length, data) triple on success
     * @uses Connection_Oscar_Flap::decode() To unwrap FLAP package
     * @throws Connection_Exception When no connections found,
     *                              when failed receiving data
     */
    protected function _recv()
    {
        if (!is_resource($this->_socket)) {
            throw new Connection_Exception(
                'No connection found',
                Connection_Exception::IO_ERROR
            );
        }

        $hdr = $data = '';

        if (false === @socket_recv($this->_socket, $hdr, 6, MSG_PEEK)) {
            throw new Connection_Exception(
                'Failed receiving FLAP header: ' .
                    socket_strerror(socket_last_error($this->_socket)),
                Connection_Exception::IO_ERROR
            );
        }

        try {
            $flap = Connection_Oscar_Flap::decode($hdr, false);
        } catch (Exception $e) {
            return false;
        }

        if (false === @socket_recv($this->_socket, $flap, 6 + $flap['length'], MSG_WAITALL)) {
            throw new Connection_Exception(
                'Failed receiving FLAP data: ' .
                    socket_strerror(socket_last_error($this->_socket)),
                Connection_Exception::IO_ERROR
            );
        }

        if ($this->is_verbose) {
            echo "RECV: {$flap}\n";
        }

        try {
            $flap = Connection_Oscar_Flap::decode($flap);
        } catch (Exception $e) {
            return false;
        }

        return $flap;
    }

    /**
     * Reads a FLAP response and returns decoded SNAC with optional
     * family/subtype check
     *
     * Disconnects immediately (and dirty) if no SNAC packets received
     * or when family/subtype differs from the ones requested.
     *
     * @param int $family  Expected SNAC family
     * @param int $subtype Expected SNAC subtype
     * @return Connection_Oscar_Snac|false
     */
    protected function _recv_snac($family=null, $subtype=null)
    {
        $response = $this->_recv();

        if (Connection_Oscar_Flap::CHANNEL_SNAC == $response['channel']) {
            $response = Connection_Oscar_Snac::decode($response['data']);

            if ((!$family || ($family == $response['family'])) &&
                (!$subtype || ($subtype == $response['subtype']))) {

                return $response;
            }
        }

        $this->close();

        return false;
    }

    /**
     * Initiates OSCAR connection
     *
     * @return bool
     */
    protected function _handshake()
    {
        $version = pack('N', self::PROTOCOL_VERSION);

        $this->_send(
            $version . ($this->_cookie
                ? Connection_Oscar_Tlv::encode(0x06, $this->_cookie)
                : ''),
            Connection_Oscar_Flap::CHANNEL_OPEN
        );

        $response = $this->_recv();

        return ((Connection_Oscar_Flap::CHANNEL_OPEN == $response['channel']) &&
                ($version == $response['data']));
    }

    /**
     * Opens a socket connection to arbitrary host
     *
     * @param string $host Host name or IP and port
     * @return resource|false Socket resource on success
     * @uses ::close() To close currently opened connection
     * @uses ::init()  To open OSCAR connection
     * @throws Connection_Exception When failed to open a socket,
     *                              when failed to unblock a socket,
     *                              on connection failure or timeout
     */
    protected function _connect($host)
    {
        $this->close();

        list($host, $port) = explode(':', $host, 2);

        if (!$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) {
            throw new Connection_Exception(
                "Failed opening a socket to {$host}:{$port}",
                Connection_Exception::IO_ERROR
            );
        }

        if (!socket_set_nonblock($socket)) {
            throw new Connection_Exception(
                'Failed unblocking socket',
                Connection_Exception::IO_ERROR
            );
        }

        if ($this->is_verbose) {
            echo "CONN: {$host}:{$port}\n";
        }

        $start = time();

        $errors_not_ready = array(SOCKET_EALREADY, SOCKET_EINPROGRESS);
        while (!@socket_connect($socket, $host, $port)) {
            $error = socket_last_error($socket);

            if (SOCKET_EISCONN == $error) {
                break;
            }

            if ($this->_timeout <= (time() - $start)) {
                socket_close($socket);
                throw new Connection_Exception(
                    'Connection timeout',
                    Connection_Exception::IO_ERROR
                );
            }

            if (!in_array($error, $errors_not_ready)) {
                socket_close($socket);
                throw new Connection_Exception(
                    'Connection failed: ' . socket_strerror($error),
                    Connection_Exception::IO_ERROR
                );
            }
        }

        socket_set_block($socket);

        return (($this->_socket = $socket) && $this->_handshake());
    }

    /**
     * Does the initial authentication
     *
     * @param string $uin  UIN (screen name)
     * @param string $pass Password
     * @return bool
     */
    protected function _authenticate($uin, $pass)
    {
        if (!$this->_connect(self::AUTH_SERVER)) {
            $this->close();
            return false;
        }

        // Key request SNAC
        $reqid = rand(0, 0xffff);
        $this->_send_snac(
            Connection_Oscar_Tlv::encode(array(0x01 => $uin,
                                               0x4c => '')),
            Connection_Oscar_Snac::FAMILY_AUTH, 0x06, 0x00, $reqid
        );
        $response = $this->_recv_snac(Connection_Oscar_Snac::FAMILY_AUTH, 0x07);
        if (!$response) {
            return false;
        }

        // Auth request SNAC
        $a = unpack('nlength', $response['data']);
        $data = $this->_client_details;
        $data[0x01] = $uin;
        $data[0x25] = $this->get_pass_hash(
            $pass,
            substr($response['data'], 2, $a['length'])
        );
        $this->_send_snac(
            Connection_Oscar_Tlv::encode($data),
            Connection_Oscar_Snac::FAMILY_AUTH, 0x02, 0x00, $reqid
        );
        $response = $this->_recv_snac(Connection_Oscar_Snac::FAMILY_AUTH, 0x03);
        if (!$response) {
            return false;
        }

        $this->close();

        $response = Connection_Oscar_Tlv::decode($response['data']);
        if (empty($response[0x05]) || empty($response[0x06])) {
            return false;
        }

        list($this->_bos, $this->_cookie) =
            array($response[0x05], $response[0x06]);

        return true;
    }

    /**
     * Logs into BOS server
     *
     * @return bool
     */
    private function _login_bos()
    {
        if (!$this->_connect($this->_bos)) {
            $this->close();
            return false;
        }

        // Server ready SNAC
        $response = $this->_recv_snac(Connection_Oscar_Snac::FAMILY_CONTROL, 0x03);
        if (!$response) {
            return false;
        }

        $this->_bos_families = array_values(unpack('n*family', $response['data']));
        if (empty($this->_bos_families)) {
            $this->close();
            return false;
        }

        // Client versions SNAC
        $versions = array();

        $codec = new Connection_Oscar_Snac();
        $a = $codec->get_family_versions($this->_bos_families);
        foreach ($a as $k => $v) {
            $versions[] = pack('nn', $k, $v);
        }
        unset($codec);

        $this->_send_snac(implode('', $versions),
                          Connection_Oscar_Snac::FAMILY_CONTROL,
                          0x17);
        do {
            // We may receive a SNAC(01,15) (well-known URLs) before
            // SNAC(01,18) we need
            $response = $this->_recv_snac(Connection_Oscar_Snac::FAMILY_CONTROL);
            if (!$response) {
                return false;
            }
        } while (0x18 != $response['subtype']);

        // Rate info request SNAC
        $this->_send_snac(null,
                          Connection_Oscar_Snac::FAMILY_CONTROL,
                          0x06);
        do {
            // We may receive a SNAC(01,13) (MOTD) before
            // SNAC(01,07) we need
            $response = $this->_recv_snac(Connection_Oscar_Snac::FAMILY_CONTROL);
            if (!$response) {
                return false;
            }
        } while (0x07 != $response['subtype']);

        // Rate ack SNAC
        $a = array();
        $rate_info = new Connection_Oscar_RateInfo($response['data']);
        foreach (array_keys($rate_info->get_groups()) as $id) {
            $a[] = pack('n', $id);
        }
        unset($rate_info);

        $this->_send_snac(implode('', $a),
                          Connection_Oscar_Snac::FAMILY_CONTROL,
                          0x08);

        return true;
    }

    /**
     * Logs out of AIM system
     */
    public function logout()
    {
        $this->close();

        $this->_cookies = $this->_bos = null;
        $this->_bos_families = array();

        return $this;
    }

    /**
     * Authenticates and logs into a BOS server
     *
     * @param string $uin  UIN (screen name)
     * @param string $pass Account password
     * @return bool
     * @uses ::_authenticate() To authenticate
     * @uses ::_login_bos() To log into BOS server
     */
    public function login($uin, $pass)
    {
        $this->logout();

        return ($this->_authenticate($uin, $pass) && $this->_login_bos());
    }
}
