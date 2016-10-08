<?php

class Pool_Sesame_Abstract extends Pool_Db_Abstract
{
    public function __construct(array $config=array())
    {
        parent::__construct($config);

        if (!empty($this->_config['project']['name'])) {
            $name = strtolower($this->_config['project']['name']);
            foreach ($this->_stmts as &$stmt) {
                if (!is_array($stmt)) {
                    $stmt = array(
                        'db'    => 'sesame',
                        'query' => (string)$stmt,
                    );
                }
                $stmt['query'] = str_replace('[PROJECT]', $name, $stmt['query']);
            }
        }
    }
}
