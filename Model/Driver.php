<?php

namespace Cabride\Model;

use Core\Model\Base;

/**
 * Class Driver
 * @package Cabride\Model
 */
class Driver extends Base
{
    /**
     * Driver constructor.
     * @param array $params
     * @throws \Zend_Exception
     */
    public function __construct($params = [])
    {
        parent::__construct($params);
        $this->_db_table = 'Cabride\Model\Db\Table\Driver';
        return $this;
    }
}