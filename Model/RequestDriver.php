<?php

namespace Cabride\Model;

use Core\Model\Base;

/**
 * Class RequestDriver
 * @package Cabride\Model
 *
 */
class RequestDriver extends Base
{
    /**
     * Request constructor.
     * @param array $params
     * @throws \Zend_Exception
     */
    public function __construct($params = [])
    {
        parent::__construct($params);
        $this->_db_table = 'Cabride\Model\Db\Table\RequestDriver';
        return $this;
    }
}