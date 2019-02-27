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
     * @param $context
     * @param $fields
     * @return mixed
     * @throws \Zend_Exception
     */
    public static function populateExtended ($context, $fields)
    {
        $session = $context["session"];
        $driver = (new Driver())->find($session->getCustomerId(), "customer_id");
        if ($driver->getId()) {
            foreach ($fields as &$field) {
                switch ($field["key"]) {
                    case "vehicle_model":
                        $field["value"] = $driver->getVehicleModel();
                        break;
                    case "vehicle_license_plate":
                        $field["value"] = $driver->getVehicleLicensePlate();
                        break;
                    case "driver_license":
                        $field["value"] = $driver->getDriverLicense();
                        break;
                    case "base_address":
                        $field["value"] = $driver->getBaseAddress();
                        break;
                    case "pickup_radius":
                        $field["value"] = $driver->getPickupRadius();
                        break;
                    case "driver_photo":
                        $field["value"] = $driver->getDriverPhoto();
                        break;
                }
            }
        }

        return $fields;
    }

    /**
     * @param $context
     * @param $fields
     * @return mixed
     * @throws \Zend_Exception
     */
    public static function saveExtended ($context, $fields)
    {
        $session = $context["session"];
        $driver = (new Driver())->find($session->getCustomerId(), "customer_id");
        if ($driver->getId()) {
            foreach ($fields as &$field) {
                switch ($field["key"]) {
                    case "vehicle_model":
                        $driver->setVehicleModel($field["value"]);
                        break;
                    case "vehicle_license_plate":
                        $driver->setVehicleLicensePlate($field["value"]);
                        break;
                    case "driver_license":
                        $driver->setDriverLicense($field["value"]);
                        break;
                    case "base_address":
                        $driver->setBaseAddress($field["value"]);
                        break;
                    case "pickup_radius":
                        $driver->setPickupRadius($field["value"]);
                        break;
                    case "driver_photo":
                        $driver->setDriverPhoto($field["value"]);
                        break;
                }
            }
            $driver->save();
        }

        return $fields;
    }

    /**
     * @return bool
     * @throws \Siberian\Exception
     */
    public function getMustFillVehicle()
    {
        if (empty($this->getVehicleLicensePlate())) {
            return true;
        }

        if (empty($this->getDriverLicense())) {
            return true;
        }

        $cabride = Cabride::getCurrent();
        if ($cabride->getPricingMode() === "driver") {
            if (empty($this->getBaseFare())) {
                return true;
            }

            if (empty($this->getDistanceFare()) && empty($this->getTimeFare())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generic method to export consistently to JSON
     *
     * @return array|mixed|null|string
     */
    public function toJson()
    {
        $data = $this->getData();
        $data["hasVehicle"] = (boolean) $data["vehicle_id"];
        $data["vehicle_id"] = (boolean) $data["vehicle_id"];
        $data["base_fare"] = (float) $data["base_fare"];
        $data["distance_fare"] = (float) $data["distance_fare"];
        $data["time_fare"] = (float) $data["time_fare"];
        $data["base_latitude"] = (float) $data["base_latitude"];
        $data["base_longitude"] = (float) $data["base_longitude"];
        $data["pickup_radius"] = (integer) $data["pickup_radius"];
        $data["value_id"] = (integer) $data["value_id"];
        $data["driver_id"] = (integer) $data["driver_id"];
        $data["customer_id"] = (integer) $data["customer_id"];
        $data["latitude"] = (integer) $data["latitude"];
        $data["longitude"] = (integer) $data["longitude"];
        $data["id"] = (integer) $data["id"];

        return $data;
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