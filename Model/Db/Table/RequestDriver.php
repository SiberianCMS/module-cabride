<?php

namespace Cabride\Model\Db\Table;

use Core_Model_Db_Table;

/**
 * Class RequestDriver
 * @package Cabride\Model\Db\Table
 */
class RequestDriver extends Core_Model_Db_Table
{
    /**
     * @var string
     */
    protected $_name = "cabride_request_driver";

    /**
     * @var string
     */
    protected $_primary = "request_driver_id";

    /**
     * @param $driverId
     * @param $statuses
     * @return mixed
     */
    public function fetchForDriver($driverId, $statuses)
    {
        $select = $this->_db
            ->select()
            ->from($this->_name)
            ->joinInner(
                "cabride_request",
                "cabride_request_driver.request_id = cabride_request.request_id")
            ->where("cabride_request_driver.driver_id = ?", $driverId)
            ->where("cabride_request_driver.status IN (?)", $statuses)
            ->where("cabride_request.status = cabride_request_driver.status");

        return $this->toModelClass($this->_db->fetchAll($select));
    }
}
