<?php
/**
 * @package Captcha
 */

/**
 * CAPTCHA decoders' factory
 *
 * @package Captcha
 * @subpackage Decoder
 */
abstract class Captcha_Decoder_Factory
{
    /**
     * Default decoder
     */
    const DEFAULT_DECODER = 'manual';


    /**
     * Decoders pool
     *
     * @var array
     */
    static protected $_decoders = array();


    /**
     * Creates specific CAPTCHA decoder, returns cached instance when
     * available
     *
     * @param array|string $config CAPTCHA-related section of project
     *                             configuration or just decoder name
     * @param Log_Interface $log Optional logger to use
     * @return Captcha_Decoder_Abstract instance
     */
    static public function factory($config, Log_Interface $log=null)
    {
        if (!is_array($config)) {
            $config = array('decoder' => $config
                ? $config
                : self::DEFAULT_DECODER
            );
        }
        $config['decoder'] = ucfirst($config['decoder']);
        if (!$decoder = &self::$_decoders[$config['decoder']]) {
            $class = str_replace(
                '_Factory',
                "_{$config['decoder']}",
                __CLASS__
            );
            $decoder = new $class();
        }
        if (!empty($config['userpass'])) {
            $decoder->set_credentials($config['userpass']);
        } else if (!empty($config['user'])) {
            $decoder->set_credentials($config['user'], @$config['pass']);
        }
        foreach (array('timeout', 'match') as $k) {
            if (!empty($config[$k])) {
                $decoder->{$k} = $config[$k];
            }
        }
        if ($log) {
            $decoder->set_log($log);
        }
        return $decoder;
    }
}
