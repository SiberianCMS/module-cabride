<?php

namespace Cabride\Model;

use Core\Model\Base;

/**
 * Class Driver
 * @package Cabride\Model
 *
 * @method Db\Table\Driver getTable();
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

    /**
     * @param $valueId
     * @param $formula
     * @return mixed
     */
    public function findNearestOnline($valueId, $formula)
    {
        return $this->getTable()->findNearestOnline($valueId, $formula);
    }
}