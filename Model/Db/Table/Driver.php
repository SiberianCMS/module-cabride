<?php

namespace Cabride\Model\Db\Table;

use Core_Model_Db_Table;
use Cabride\Model\Cabride as ModelCabride;
use Cabride\Model\Driver as ModelDriver;

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
     * @throws \Zend_Exception
     */
    public function findNearestOnline($valueId, $formula)
    {
        $settings = (new ModelCabride())
            ->find($valueId, "value_id");
        $unit = $settings->getDistanceUnit();
        $radius = $settings->getSearchRadius();

        // Convert miles to meters
        if ($unit === "mi") {
            $radius = $radius * 1609.34;
        } else {
            $radius = $radius * 1000;
        }

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
            ->where("d.is_online = ?", 1)
            ->where("d.status = ?", "active") // Driver must be activated!
            ->where("(d.latitude != 0 AND d.longitude != 0)")
            ->having("distance < ?", $radius)
        ;

        return $this->toModelClass($this->_db->fetchAll($select));
    }

    /**
     * @param $id
     * @param null $field
     * @return ModelDriver
     * @throws \Zend_Exception
     */
    public function findExtended($id, $field = null)
    {
        $select = $this->_db
            ->select()
            ->from(
                ["d" => $this->_name]
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
            );

        if ($field !== null) {
            $select->where("d.{$field} = ?", $id);
        } else {
            $select->where("d.{$this->_primary} = ?", $id);
        }

        return (new ModelDriver())->setData($this->_db->fetchRow($select));
    }

    /**
     * @param $valueId
     * @return mixed
     */
    public function fetchForValueId($valueId)
    {
        $select = $this->_db->select()
            ->from(
                [
                    "driver" => $this->_name,
                ],
                [
                    "*",
                ]
            )
            ->joinInner(
                "customer",
                "driver.customer_id = customer.customer_id",
                [
                    "firstname",
                    "lastname",
                    "nickname",
                    "email",
                    "image",
                ]
            )
            ->joinLeft(
                "cabride_vehicle",
                "driver.vehicle_id = cabride_vehicle.vehicle_id",
                ["type", "icon", "base_fare", "distance_fare", "time_fare", "base_address"]
            )
            ->where("driver.value_id = ?", $valueId);

        return $this->toModelClass($this->_db->fetchAll($select));
    }

    /**
     * @param $driverId
     * @return string
     */
    public function fetchRating($driverId)
    {
        $select = $this->_db->select()
            ->from(
                [
                    "cabride_request",
                ],
                [
                    "average_rating" => new \Zend_Db_Expr("SUM(course_rating) / COUNT(*)")
                ]
            )
            ->where("cabride_request.driver_id = ?", $driverId)
            ->where("cabride_request.course_rating > 0");

        return $this->_db->fetchOne($select);
    }

}
