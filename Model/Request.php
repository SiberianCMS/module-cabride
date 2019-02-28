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
    const SOURCE_CLIENT = "client";
    const SOURCE_DRIVER = "driver";
    const SOURCE_CRON = "cron";
    const SOURCE_ADMIN = "admin";

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
     * @param $valueId
     * @param $clientId
     * @return mixed
     */
    public function findExtended($valueId, $clientId)
    {
        return $this->getTable()->findExtended($valueId, $clientId) ;
    }

    /**
     * @param $valueId
     * @param $clientId
     * @param $status
     * @return mixed
     */
    public function findForDriver($valueId, $clientId, $status)
    {
        return $this->getTable()->findForDriver($valueId, $clientId, $status);
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
            "size" => "600x220",
            "scale" => "2"
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
     * @return Request[]
     */
    public function fetchPending()
    {
        return $this->getTable()->fetchPending();
    }

    /**
     * @return Request[]
     */
    public function fetchPendingForClient($clientId)
    {
        return $this->getTable()->fetchPendingForClient($clientId);
    }

    /**
     * @param $status
     * @param $source
     * @throws \Zend_Exception
     */
    public function changeStatus($status, $source)
    {
        $statusFrom = $this->getStatus();
        $statusTo = $status;

        $this
            ->setStatus($status)
            ->save();

        // Trigger notifications!
        $this->notify();

        self::log($this, $statusFrom, $statusTo, $source);
    }

    /**
     * @throws \Zend_Exception
     */
    public function notify ()
    {
        $valueId = $this->getValueId();
        $cabride = (new \Application_Model_Option_Value())->find($valueId);
        $application = (new \Application_Model_Application())->find($cabride->getAppId());
        $appKey = $application->getKey();
        $clientId = $this->getClientId();
        $requestId = $this->getRequestId();

        // If there is a driver yet ...
        $driverId = $this->getDriverId();

        $status = $this->getStatus();
        switch ($status) {
            case "pending";
                // Notify all drivers found!
                $drivers = (new RequestDriver())->findAll([
                    "request_id" => $requestId,
                    "status" => "pending"
                ]);

                // Send push to passenger!
                $title = p__("cabride",
                    "Ride request!");
                $message = p__("cabride",
                    "You have a new ride request!");
                $actionUrl = "/{$appKey}/cabride/mobile_pending_requests/index";

                foreach ($drivers as $driver) {
                    $driver = (new Driver())->find($driver->getId(), "driver_id");
                    $customerId = $driver->getCustomerId();
                    $pushDevice = (new PushDevice())
                        ->find($customerId, "customer_id");

                    $pushDevice->sendMessage($title, $message, $requestId, "driver", $status, $actionUrl);
                }

                break;
            case "accepted";
                // Send push to passenger!
                $title = p__("cabride",
                    "Ride accepted!");
                $message = p__("cabride",
                    "A driver accepted your request, he will be on your way soon!");
                $actionUrl = "/{$appKey}/cabride/mobile_my_rides/index";

                $client = (new Client())->find($clientId, "client_id");
                $customerId = $client->getCustomerId();
                $pushDevice = (new PushDevice())
                    ->find($customerId, "customer_id");

                $pushDevice->sendMessage($title, $message, $requestId, "passenger", $status, $actionUrl);
                break;
            case "onway";
                // Send push to passenger!
                $title = p__("cabride",
                    "Driver on your way!");
                $message = p__("cabride",
                    "You driver is on your way!");

                $actionUrl = "/{$appKey}/cabride/mobile_my_rides/index";

                $client = (new Client())->find($clientId, "client_id");
                $customerId = $client->getCustomerId();
                $pushDevice = (new PushDevice())
                    ->find($customerId, "customer_id");

                $pushDevice->sendMessage($title, $message, $requestId, "passenger", $status, $actionUrl);
                break;
            case "inprogress";

                break;
            case "declined";
                // Inform the user it's over (Request changes to 'decline' only if all drivers declined it)
                $title = p__("cabride",
                    "No drivers found!");
                $message = p__("cabride",
                    "Sorry, there was no driver able to take your ride, please try again!");

                $actionUrl = "/{$appKey}/cabride/mobile_home/index";

                $client = (new Client())->find($clientId, "client_id");
                $customerId = $client->getCustomerId();
                $pushDevice = (new PushDevice())
                    ->find($customerId, "customer_id");

                $pushDevice->sendMessage($title, $message, $requestId, "passenger", $status, $actionUrl);

                break;
            case "done";
                // Send push to passenger!
                $title = p__("cabride",
                    "Your ride is done!");
                $message = p__("cabride",
                    "Thanks for riding with us, hope to see you again soon!");

                $actionUrl = "/{$appKey}/cabride/mobile_home/index";

                $client = (new Client())->find($clientId, "client_id");
                $customerId = $client->getCustomerId();
                $pushDevice = (new PushDevice())
                    ->find($customerId, "customer_id");

                $pushDevice->sendMessage($title, $message, $requestId, "passenger", $status, $actionUrl);
                break;
            case "aborted";

                break;
            case "expired";
                // Send push to passenger!
                $title = p__("cabride",
                    "You request expired!");
                $message = p__("cabride",
                    "Sorry there was no driver available to drive you, please try again!");

                $actionUrl = "/{$appKey}/cabride/mobile_home/index";

                $client = (new Client())->find($clientId, "client_id");
                $customerId = $client->getCustomerId();
                $pushDevice = (new PushDevice())
                    ->find($customerId, "customer_id");

                $pushDevice->sendMessage($title, $message, $requestId, "passenger", $status, $actionUrl);
                break;
        }
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

    /**
     * @param \Cron_Model_Cron $cron
     * @throws \Zend_Exception
     */
    public static function toExpire($cron)
    {
        $now = time();
        // Fetch all pending requests
        $pendingRequests = (new self())->fetchPending();
        foreach ($pendingRequests as $pendingRequest) {
            $data = $pendingRequest->getData();
            $expireAt = $data["timestamp"] + $data["search_timeout"];
            $id  = $data["request_id"];

            if ($now > $expireAt) {
                $cron->log("[Cabride] now {$now} / {$expireAt}.");
                $cron->log("[Cabride] marking request_id {$id} as expired.");

                $pendingRequest->changeStatus("expired", self::SOURCE_CRON);
            }
        }
    }
}