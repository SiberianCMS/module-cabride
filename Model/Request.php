<?php

namespace Cabride\Model;

use Core\Model\Base;
use Siberian_Google_Geocoding as Geocoding;
use Siberian\Feature;
use Siberian\Json;

/**
 * Class Request
 * @package Cabride\Model
 *
 * @method Db\Table\Request getTable()
 * @method $this setValueId(integer $valueId)
 * @method $this setVehicleId(integer $vehicleId)
 * @method $this setClientId(integer $clientId)
 * @method $this setDriverId(integer $driverId)
 * @method $this setStaticImage(string $staticImage)
 * @method $this setStatus(string $status)
 * @method $this setEstimatedCost(float $estimatedCost)
 * @method $this setDistance(integer $distance)
 * @method $this setDuration(integer $duration)
 * @method $this setFromAddress(string $fromAddress)
 * @method $this setFromLat(float $fromLat)
 * @method $this setFromLng(float $fromLng)
 * @method $this setToAddress(string $toAddress)
 * @method $this setToLat(float $toLat)
 * @method $this setToLng(float $toLng)
 * @method $this setRequestMode(string $requestMode)
 * @method $this setRawRoute(string $rawRoute)
 */
class Request extends Base
{
    /**
     * Request constructor.
     * @param array $params
     * @throws \Zend_Exception
     */
    public function __construct($params = [])
    {
        parent::__construct($params);
        $this->_db_table = 'Cabride\Model\Db\Table\Request';
        return $this;
    }

    /**
     * Sends a push notification to the driver!
     */
    public function notifyDriver()
    {

    }

    public function findExtended($valueId, $clientId)
    {
        return $this->getTable()->findExtended($valueId, $clientId) ;
    }

    /**
     * @param $route
     * @param $optionValue
     * @param $gmapsKey
     * @return string
     * @throws \Siberian\Exception
     */
    public static function staticMapFromRoute ($route, $optionValue, $gmapsKey)
    {
        $request = $route["request"];
        $origin = $request["origin"]["location"];
        $destination = $request["destination"]["location"];
        $lat = $origin["lat"];
        $lng = $origin["lng"];
        $destLat = $destination["lat"];
        $destLng = $destination["lng"];

        $points = $route["routes"][0]["overview_polyline"];
        $options = [
            "markers" => [
                "color:grey|size:mid|{$lat},{$lng}",
                "color:green|size:mid|{$destLat},{$destLng}",
            ],
            "path" => "enc:{$points}",
        ];

        $mapStaticUri = Geocoding::mapStatic($gmapsKey, $options);
        $rawImage = file_get_contents($mapStaticUri);
        $uuid = uniqid();

        $newFile = Feature::createFile($optionValue, $rawImage, "$uuid.jpg");

        return $newFile;
    }

    /**
     * @param $clientId
     * @param Vehicle $vehicleType
     * @param $valueId
     * @param $route
     * @param $staticMap
     * @param $source
     * @return $this
     * @throws \Zend_Exception
     */
    public function createRideRequest($clientId, $vehicleType, $valueId, $route, $staticMap, $source)
    {
        $travel = $route["request"];
        $leg = $route["routes"][0]["legs"][0];
        $distanceKm = ceil($leg["distance"]["value"] / 1000);
        $durationMinute = ceil($leg["duration"]["value"] / 60);

        $estimatedCost = $vehicleType->estimatePricing($distanceKm, $durationMinute, false);

        $this
            ->setValueId($valueId)
            ->setStatus("pending")
            ->setClientId($clientId)
            ->setVehicleId($vehicleType->getId())
            ->setStaticImage($staticMap)
            ->setEstimatedCost($estimatedCost)
            ->setDistance($leg["distance"]["value"])
            ->setDuration($leg["duration"]["value"])
            ->setFromAddress($leg["start_address"])
            ->setFromLat($travel["origin"]["location"]["lat"])
            ->setFromLng($travel["origin"]["location"]["lng"])
            ->setToAddress($leg["end_address"])
            ->setToLat($travel["destination"]["location"]["lat"])
            ->setToLng($travel["destination"]["location"]["lng"])
            ->setRequestMode("immediate")
            ->setRawRoute(Json::encode($route))
            ->save();

        self::log($this, "", "pending", $source);

        return $this;
    }

    /**
     * @param $request
     * @param $statusFrom
     * @param $statusTo
     * @param $source
     * @throws \Zend_Exception
     */
    public static function log($request, $statusFrom, $statusTo, $source)
    {
        $requestLog = new RequestLog();
        $requestLog
            ->setRequestId($request->getId())
            ->setValueId($request->getValueId())
            ->setStatusFrom($statusFrom)
            ->setStatusTo($statusTo)
            ->setSource($source)
            ->save();
    }
}