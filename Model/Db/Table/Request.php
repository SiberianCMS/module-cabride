<?php

namespace Cabride\Model\Db\Table;

use Core_Model_Db_Table;

/**
 * Class Request
 * @package Cabride\Model\Db\Table
 */
class Request extends Core_Model_Db_Table
{
    /**
     * @var string
     */
    protected $_name = "cabride_request";

    /**
     * @var string
     */
    protected $_primary = "request_id";

    /**
     * @param $valueId
     * @param $clientId
     * @return mixed
     */
    public function findExtended($valueId, $clientId)
    {
        $select = $this->_db->select()
            ->from(
                ["request" => $this->_name],
                [
                    "*",
                    "timestamp" =>new \Zend_Db_Expr("UNIX_TIMESTAMP(request.created_at)"),
                ]
            )
            ->joinInner(
                ["vehicle" => "cabride_vehicle"],
                "vehicle.vehicle_id = request.vehicle_id",
                ["type", "icon", "base_fare", "distance_fare", "time_fare"]
            )
            ->joinInner(
                "cabride",
                "cabride.value_id = request.value_id",
                ["search_timeout"]
            )
            ->where("request.value_id = ?", $valueId)
            ->where("request.client_id = ?", $clientId)
            ->order("updated_at DESC");

        return $this->toModelClass($this->_db->fetchAll($select));
    }

    /**
     * @param $valueId
     * @param $driverId
     * @param $status
     * @return mixed
     */
    public function findForDriver($valueId, $driverId, $status)
    {
        $now = time();
        $select = $this->_db->select()
            ->from(
                ["request" => $this->_name],
                [
                    "*",
                    "expires" => new \Zend_Db_Expr("(UNIX_TIMESTAMP(request.created_at) + cabride.search_timeout)"),
                    "timestamp" => new \Zend_Db_Expr("UNIX_TIMESTAMP(request.created_at)"),
                ]
            )
            ->joinInner(
                ["request_driver" => "cabride_request_driver"],
                "request_driver.request_id = request.request_id",
                [
                    "driver_status" => "status"
                ]
            )
            ->joinInner(
                ["vehicle" => "cabride_vehicle"],
                "vehicle.vehicle_id = request.vehicle_id",
                ["type", "icon", "base_fare", "distance_fare", "time_fare"]
            )
            ->joinInner(
                "cabride",
                "cabride.value_id = request.value_id",
                ["search_timeout"]
            )
            ->where("request.value_id = ?", $valueId)
            ->where("request_driver.status = ?", $status)
            ->where("request_driver.driver_id = ?", $driverId)
            ->order("updated_at DESC");

        // We only show declined requests, if they still are pending!
        if ($status === "declined") {
            $select->where("request.status = ?", "pending");
        }

        // Only fetch pending/declined if they are not expired too!
        if (in_array($status, ["pending", "declined"])) {
            $select->having("expires > ?", $now);
        }

        return $this->toModelClass($this->_db->fetchAll($select));
    }

    /**
     * @return mixed
     */
    public function fetchPending()
    {
        $select = $this->_db->select()
            ->from(
                ["request" => $this->_name],
                [
                    "status",
                    "timestamp" => new \Zend_Db_Expr("UNIX_TIMESTAMP(request.created_at)"),
                    "*"
                ]
            )
            ->joinInner(
                "cabride",
                "cabride.value_id = request.value_id",
                [
                    "search_timeout"
                ]
            )
            ->where("request.status = ?", "pending");

        return $this->toModelClass($this->_db->fetchAll($select));
    }
}
