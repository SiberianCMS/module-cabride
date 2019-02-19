<?php

namespace Cabride\Model;

use Core\Model\Base;
use Siberian\Json;

/**
 * Class Driver
 * @package Cabride\Model
 *
 * @method Db\Table\Driver getTable()
 * @method integer getDriverId()
 * @method float getBaseFare()
 * @method float getDistanceFare()
 * @method float getTimeFare()
 * @method integer getVehicleId()
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
     * @param $id
     * @param null $field
     * @return Driver
     * @throws \Zend_Exception
     */
    public function findExtended($id, $field = null)
    {
        return $this->getTable()->findExtended($id, $field);
    }

    /**
     * @param $requestId
     */
    public function notifyNewrequest ($requestId)
    {
        // @todo notify driver!
    }

    /**
     * @return array
     */
    public function getFilteredData()
    {
        $filter = [
            "driver_id",
            "type",
            "icon",
            "vehicle_model",
        ];

        return array_intersect_key($this->getData(), array_flip($filter));
    }

    /**
     * @param $valueId
     * @return Driver[]
     */
    public function fetchForValueId($valueId)
    {
        return $this->getTable()->fetchForValueId($valueId);
    }

    /**
     * @param $valueId
     * @param $formula
     * @return Driver[]
     * @throws \Zend_Exception
     */
    public function findNearestOnline($valueId, $formula)
    {
        return $this->getTable()->findNearestOnline($valueId, $formula);
    }
}