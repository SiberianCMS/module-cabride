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
            ->where("driver_id = ?", $driverId)
            ->where("status IN (?)", $statuses);

        return $this->toModelClass($this->_db->fetchAll($select));
    }
}
