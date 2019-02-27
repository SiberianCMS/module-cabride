<?php

namespace Cabride\Model;

use Core\Model\Base;

/**
 * Class RequestDriver
 * @package Cabride\Model
 *
 * @method Db\Table\RequestDriver getTable()
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

    /**
     * @param $driverId
     * @param $statuses
     * @return mixed
     */
    public function fetchForDriver($driverId, $statuses)
    {
        return $this->getTable()->fetchForDriver($driverId, $statuses);
    }
}