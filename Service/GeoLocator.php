<?php
/**
 * @package Service
 */

/**
 * @package Service
 * @subpackage GeoLocator
 */
abstract class Service_GeoLocator
{
    const DEFAULT_COUNTRY = 'US';


    static protected $_hosts = array(
        '85.17.172.11',
        '87.117.253.181',
    );
    static protected $_ports = array(
        8123,
        8124,
        8125,
        8126,
        8127,
        8128,
        8129,
        8130,
    );
    static protected $_fields = array(
        'ip',
        'country',
        'region_code',
        'region',
        'city',
        'postal_code',
        'lat',
        'lng'
    );


    /**
     * Fetches random service host
     *
     * @return array (host, port) tuple
     */
    static protected function _get_host()
    {
        return array(
            self::$_hosts[array_rand(self::$_hosts)],
            self::$_ports[array_rand(self::$_ports)],
        );
    }

    /**
     * Parses JSON-encoded location data
     *
     * @param string $s
     * @return array|false
     */
    static protected function _parse_response($s)
    {
        $a = (($a = json_decode(trim($s))) && (count(self::$_fields) == @count($a)))
            ? array_combine(self::$_fields, $a)
            : false;
        if ($a) {
            $a['locality'] = &$a['city'];
        }
        return $a;
    }


    /**
     * Tries to get IP location using connection
     *
     * @param string $ip IP to locate
     * @param Connection_Interface $conn Connection to use
     * @return array|false
     */
    static protected function _locate_by_connection($ip, Connection_Interface $conn)
    {
        $old_follow = $conn->followlocation;
        $conn->followlocation = false;
        $conn->get('http://' . implode(':', self::_get_host()) . "/{$ip}");
        $conn->followlocation = $old_follow;
        return (200 == $conn->http_code)
            ? self::_parse_response($conn->response)
            : false;
    }

    /**
     * Tries to get random location
     *
     * @param Connection_Interface $conn Connection to use
     * @param string $country Desired country
     * @return array|false
     */
    static protected function _get_random_location_by_connection($country, Connection_Interface $conn)
    {
        $old_follow = $conn->followlocation;
        $conn->followlocation = false;
        $conn->get('http://' . implode(':', self::_get_host()) . "/{$country}");
        $conn->followlocation = $old_follow;
        return (200 == $conn->http_code)
            ? self::_parse_response($conn->response)
            : false;
    }


    /**
     * Tries to get IP location using raw sockets or prepared connection
     *
     * @param string $ip Optional IP to check
     * @param Connection_Interface $conn Optional connection to use
     * @return array|false
     */
    static public function locate($ip='', Connection_Interface $conn=null)
    {
        $ip = (string)$ip;

        if ($conn) {
            return self::_locate_by_connection($ip, $conn);
        }

        $result = false;
        if ($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) {
            list($host, $port) = self::_get_host();
            if (socket_connect($sock, $host, $port)) {
                $ip .= "\n";
                while ($ip) {
                    $ip = substr($ip, socket_send($sock, $ip, strlen($ip), 0));
                }
                socket_shutdown($sock, 1);

                $buff = '';
                while (socket_recv($sock, $s, 4096, MSG_WAITALL)) {
                    $buff .= $s;
                }
                $result = self::_parse_response($buff);
            }
            socket_close($sock);
        }
        return $result;
    }

    /**
     * Tries to get random location using raw sockets or prepared connection
     *
     * @param string $country Desired country, defaults to {@link ::DEFAULT_COUNTRY}
     * @param Connection_Interface $conn Optional connection to use
     * @return array|false
     */
    static public function get_random_location($country=null, Connection_Interface $conn=null)
    {
        $country = $country
            ? (string)$country
            : self::DEFAULT_COUNTRY;

        if ($conn) {
            return self::_get_random_location_by_connection($country, $conn);
        }

        $result = false;
        if ($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) {
            list($host, $port) = self::_get_host();
            if (socket_connect($sock, $host, $port)) {
                $country .= "\n";
                while ($country) {
                    $country = substr($country, socket_send($sock, $country, strlen($country), 0));
                }
                socket_shutdown($sock, 1);

                $buff = '';
                while (socket_recv($sock, $s, 4096, MSG_WAITALL)) {
                    $buff .= $s;
                }
                $result = self::_parse_response($buff);
            }
            socket_close($sock);
        }
        return $result;
    }
}
