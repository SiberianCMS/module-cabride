<?php

namespace Cabride\Model;

use Core\Model\Base;

/**
 * Class Driver
 * @package Cabride\Model
 *
 * @method integer getId()
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
        $this->_db_table = Db\Table\Driver::class;
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
    public function getProfileErrors()
    {
        $errors = [];
        if (empty($this->getVehicleLicensePlate())) {
            $errors[] = p__("cabride", "Vehicle license plate");
        }

        if (empty($this->getDriverLicense())) {
            $errors[] = p__("cabride", "Driving license");
        }

        $cabride = Cabride::getCurrent();
        if ($cabride && $cabride->getPricingMode() === 'driver') {
            if (empty($this->getBaseFare())) {
                $errors[] = p__("cabride", "Base fare");
            }

            if (empty($this->getDistanceFare()) && empty($this->getTimeFare())) {
                $errors[] = p__("cabride", "Distance and/or time fare");
            }
        }

        $customer = (new \Customer_Model_Customer())->find($this->getCustomerId());
        if (empty($customer->getMobile())) {
            $errors[] = p__("cabride", "Mobile phone number");
        }

        if (empty($this->getBaseAddress()) ||
            ($this->getBaseLatitude() == 0 && $this->getBaseLongitude() == 0)) {
            $errors[] = p__("cabride", "Incorrect base address");
        }

        return $errors;
    }

    /**
     * Generic method to export consistently to JSON
     *
     * @return array|mixed|null|string
     */
    public function toJson($optionValue = null, $baseUrl = '')
    {
        $data = $this->getData();
        $data["hasVehicle"] = (boolean) $data["vehicle_id"];
        $data["vehicle_id"] = (integer) $data["vehicle_id"];
        $data["base_fare"] = (float) $data["base_fare"];
        $data["distance_fare"] = (float) $data["distance_fare"];
        $data["time_fare"] = (float) $data["time_fare"];
        $data["extra_seat_fare"] = (float) $data["extra_seat_fare"];
        $data["seat_distance_fare"] = (float) $data["seat_distance_fare"];
        $data["seat_time_fare"] = (float) $data["seat_time_fare"];
        $data["tour_base_fare"] = (float) $data["tour_base_fare"];
        $data["tour_time_fare"] = (float) $data["tour_time_fare"];
        $data["extra_seat_tour_base_fare"] = (float) $data["extra_seat_tour_base_fare"];
        $data["extra_seat_tour_time_fare"] = (float) $data["extra_seat_tour_time_fare"];
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
     * @param $km
     * @param $minute
     * @param int $seats
     * @return array
     * @throws \Zend_Exception
     */
    public function estimatePricing($km, $minute, $seats = 1)
    {
        $cabride = (new Cabride())->find($this->getValueId(), 'value_id');

        // Automatically get price depending on settings!
        if ($cabride->getPricingMode() === 'fixed') {
            $vehicleType = (new Vehicle())->find($this->getVehicleId());

            $base = $vehicleType->getBaseFare();
            $distance = $vehicleType->getDistanceFare();
            $time = $vehicleType->getTimeFare();
            $extraSeatFare = $vehicleType->getExtraSeatFare();
            $seatDistanceFare = $vehicleType->getSeatDistanceFare();
            $seatTimeFare = $vehicleType->getSeatTimeFare();
        } else {
            $base = $this->getBaseFare();
            $distance = $this->getDistanceFare();
            $time = $this->getTimeFare();
            $extraSeatFare = $this->getExtraSeatFare();
            $seatDistanceFare = $this->getSeatDistanceFare();
            $seatTimeFare = $this->getSeatTimeFare();
        }

        $rawPrice = $base + ($distance * $km) + ($time * $minute);

        // Seats are enabled, we add the price
        $seats = abs($seats) - 1;
        if ($seats < 0) {
            $seats = 0;
        }

        $seatPrice = 0;
        if ($cabride->getEnableSeats()) {
            $seatPrice = ($extraSeatFare * $seats) + ($seatTimeFare * $seats * $minute) + ($seatDistanceFare * $seats * $km);
        }

        $rawPrice = $rawPrice + $seatPrice;
        $price = round($rawPrice, 2, PHP_ROUND_HALF_UP);

        return [
            'price' => $price,
            'format' => self::_formatPrice($price, $cabride->getCurrency()),
        ];
    }

    /**
     * @param int $minutes
     * @param int $seats
     * @return array
     * @throws \Zend_Exception
     */
    public function estimatePricingTour($minutes = 30, $seats = 1)
    {
        $cabride = (new Cabride())->find($this->getValueId(), 'value_id');

        // Automatically get price depending on settings!
        if ($cabride->getPricingMode() === 'fixed') {
            $vehicleType = (new Vehicle())->find($this->getVehicleId());

            $base = $vehicleType->getTourBaseFare();
            $time = $vehicleType->getTourTimeFare();
            $seatBase = $vehicleType->getExtraSeatTourBaseFare();
            $seatTime = $vehicleType->getExtraSeatTourTimeFare();
        } else {
            $base = $this->getTourBaseFare();
            $time = $this->getTourTimeFare();
            $seatBase = $this->getExtraSeatTourBaseFare();
            $seatTime = $this->getExtraSeatTourTimeFare();
        }

        $rawPrice = $base + ($time * $minutes);

        // Seats are enabled, we add the price
        $seats = abs($seats) - 1;
        if ($seats < 0) {
            $seats = 0;
        }

        $seatPrice = 0;
        if ($cabride->getEnableSeats()) {
            $seatPrice = ($seatBase * $seats) + ($seatTime * $seats * $minutes);
        }

        $rawPrice = $rawPrice + $seatPrice;

        $price = round($rawPrice, 2, PHP_ROUND_HALF_UP);

        return [
            'price' => $price,
            'format' => self::_formatPrice($price, $cabride->getCurrency()),
        ];
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
     * @return Driver
     */
    public function fetchRating()
    {
        $rating = $this->getTable()->fetchRating($this->getId());

        $this->setAverageRating($rating);

        return $this;
    }

    /**
     * @param $valueId
     * @param $formula
     * @param $params
     * @return Driver[]
     * @throws \Zend_Exception
     */
    public function findNearestOnline($valueId, $formula, $params = null)
    {
        return $this->getTable()->findNearestOnline($valueId, $formula, $params);
    }
}
