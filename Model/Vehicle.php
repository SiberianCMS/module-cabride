<?php

namespace Cabride\Model;

use Core\Model\Base;

/**
 * Class Vehicle
 * @package Cabride\Model
 */
class Vehicle extends Base
{
    /**
     * Vehicle constructor.
     * @param array $params
     * @throws \Zend_Exception
     */
    public function __construct($params = [])
    {
        parent::__construct($params);
        $this->_db_table = 'Cabride\Model\Db\Table\Vehicle';
        return $this;
    }
}