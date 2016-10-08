<?php
/**
 * @package Email
 */

/**
 * Base class for e-mail service providers
 *
 * @package Email
 */
abstract class Email_Abstract extends Loggable implements Email_Interface
{
    protected $_connection = null;
    protected $_client = null;
    protected $_proxy = null;

    /**
     * New messages cache
     *
     * @var array
     */
    protected $_msgs = array();


    /**
     * For provider-specific initialization use {@link ::init()} instead
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * For provider-specific clean up use {@link ::close()} instead
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Initializes the provider, extend to your needs,
     * return $this for chaining
     */
    public function init()
    {
        $m = 'close';
        if (is_object($this->_client) && method_exists($this->_client, $m)) {
            $this->_client->{$m}();
        }
        $this->_client = $this->_proxy = null;
        $this->_msgs = array();
        return $this;
    }

    /**
     * Cleans up after the provider, extend to your needs,
     * return $this for chaining
     */
    public function close()
    {
        $this->logout();
        return $this;
    }

    /**
     * @see Email_Interface::logout()
     */
    public function logout()
    {
        $m = 'logout';
        if (is_object($this->_client) && method_exists($this->_client, $m)) {
            $this->_client->{$m}();
        }
        $this->_msgs = array();
        return $this;
    }

    /**
     * Sets proxy to use to access e-mail service
     *
     * @param string|Connection_Proxy $proxy Host, use null to remove current proxy
     */
    public function set_proxy($proxy)
    {
        $this->_proxy = null;
        if ($proxy) {
            if (!$proxy instanceof Connection_Proxy) {
                $proxy = new Connection_Proxy($proxy);
            }
            if ($proxy->is_valid) {
                $this->_proxy = $proxy;
            }
        }
        return $this;
    }

    /**
     * Returns current proxy
     *
     * @return string|false
     */
    public function get_proxy()
    {
        return $this->_proxy
            ? $this->_proxy
            : false;
    }

    public function set_connection(Connection_Interface $connection)
    {
        $this->_connection = $connection;
        return $this;
    }

    public function get_connection()
    {
        return $this->_connection;
    }

    public function get_client()
    {
        return $this->_client;
    }

    /**
     * @see Email_Interface::get_message()
     */
    public function get_message($from, $subj=null, $content_regex=null)
    {
        if (!$this->_client) {
            throw new Email_Exception(
                'E-mail client not found'
            );
        }
        $from = mb_strtolower($from);
        $subj = mb_strtolower($subj);
        foreach (array(
            constant(get_class($this->_client) . '::MAILBOX_INBOX'),
            constant(get_class($this->_client) . '::MAILBOX_SPAM'),
        ) as $mailbox) {
            $msgs = &$this->_msgs[$mailbox];
            if (null === $msgs) {
                try {
                    $msgs = $this->_client->get_messages($mailbox);
                } catch (Actor_Exception $e) {
                    $this->log($e, Log_Abstract::LEVEL_ERROR);
                    break;
                }
            }
            if (!$msgs) {
                continue;
            }
            $this->log($msgs, Log_Abstract::LEVEL_DEBUG);
            foreach ($msgs as &$msg) {
                if ((false !== mb_strpos(
                    mb_strtolower($msg['from']),
                    $from
                )) && (!$subj || (false !== mb_strpos(
                    mb_strtolower($msg['subject']),
                    $subj
                )))) {
                    try {
                        $content = $this->_client->get_message($msg);
                    } catch (Actor_Exception $e) {
                        $this->log($e, Log_Abstract::LEVEL_ERROR);
                        break;
                    }
                    $this->log($content, Log_Abstract::LEVEL_DEBUG);
                    return $content_regex
                        ? (preg_match($content_regex, $content, $a)
                            ? html_entity_decode($a[1], ENT_QUOTES)
                            : false)
                        : $content;
                }
            }
        }
        return false;
    }
}
