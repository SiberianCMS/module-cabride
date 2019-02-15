<?php

namespace Cabride\Model\Db\Table;

use Core_Model_Db_Table;

/**
 * Class Driver
 * @package Cabride\Model\Db\Table
 */
class Driver extends Core_Model_Db_Table
{
    /**
     * @var string
     */
    protected $_name = "cabride_driver";

    /**
     * @var string
     */
    protected $_primary = "driver_id";

    /**
     * @param $valueId
     * @param $formula
     * @return mixed
     */
    public function findNearestOnline($valueId, $formula)
    {
        $select = $this->_db->select()
            ->from(
                ["d" => $this->_name],
                [
                    "*",
                    "distance" => $formula
                ]
            )
            ->joinInner(
                ["v" => "cabride_vehicle"],
                "v.vehicle_id = d.vehicle_id",
                [
                    "type",
                    "icon",
                    "base_fare",
                    "distance_fare",
                    "time_fare",
                ]
            )
            ->where("d.value_id = ?", $valueId)
            ->where("(d.latitude != 0 AND d.longitude != 0)")
            //->having("d.distance < 10000")
        ;

        return $this->toModelClass($this->_db->fetchAll($select));
    }

    /**
     * @param $valueId
     * @return mixed
     */
    public function fetchForValueId($valueId)
    {
        $select = $this->_db->select()
            ->from(["driver" => $this->_name])
            ->joinInner(
                "customer",
                "driver.customer_id = customer.customer_id",
                ["firstname", "lastname", "nickname", "email", "image"]
            )
            ->joinLeft(
                "cabride_vehicle",
                "driver.vehicle_id = cabride_vehicle.vehicle_id",
                ["type", "icon", "base_fare", "distance_fare", "time_fare", "base_address"]
            )
            ->where("driver.value_id = ?", $valueId);

        return $this->toModelClass($this->_db->fetchAll($select));
    }

}
