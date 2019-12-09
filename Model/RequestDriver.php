<?php

namespace Cabride\Model;

use Core\Model\Base;

/**
 * Class RequestDriver
 * @package Cabride\Model
 *
 * @method integer getId()
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
        $this->_db_table = Db\Table\RequestDriver::class;
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