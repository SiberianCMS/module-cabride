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
            ->from(["request" => $this->_name])
            ->joinInner(
                ["vehicle" => "cabride_vehicle"],
                "vehicle.vehicle_id = request.vehicle_id",
                ["type", "icon", "base_fare", "distance_fare", "time_fare"]
            )
            ->where("request.value_id = ?", $valueId)
            ->where("request.client_id = ?", $clientId);

        return $this->toModelClass($this->_db->fetchAll($select));
    }
}
