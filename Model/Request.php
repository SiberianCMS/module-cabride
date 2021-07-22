<?php

namespace Cabride\Model;

use Core\Model\Base;
use Siberian\Exception;
use Siberian\Feature;
use Siberian\Json;
use Siberian_Google_Geocoding as Geocoding;

/**
 * Class Request
 * @package Cabride\Model
 *
 * @method integer getId()
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
    const SOURCE_CLIENT = 'client';
    const SOURCE_DRIVER = 'driver';
    const SOURCE_CRON = 'cron';
    const SOURCE_ADMIN = 'admin';

    /**
     * Request constructor.
     * @param array $params
     * @throws \Zend_Exception
     */
    public function __construct($params = [])
    {
        parent::__construct($params);
        $this->_db_table = Db\Table\Request::class;
    }

    /**
     * @param $valueId
     * @param $clientId
     * @return mixed
     * @throws \Zend_Db_Select_Exception
     * @throws \Zend_Db_Statement_Exception
     * @throws \Zend_Exception
     */
    public function findExtended($valueId, $clientId)
    {
        return $this->getTable()->findExtended($valueId, $clientId);
    }

    /**
     * @param $requestId
     * @return string
     * @throws \Zend_Db_Select_Exception
     * @throws \Zend_Db_Statement_Exception
     */
    public function findOneExtended($requestId)
    {
        return $this->getTable()->findOneExtended($requestId);
    }

    /**
     * @param $valueId
     * @param $clientId
     * @param $status
     * @return mixed
     * @throws \Zend_Exception
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
    public static function staticMapFromRoute($route, $optionValue, $gmapsKey)
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
        $uuid = uniqid('cb_', true);

        return Feature::createFile($optionValue, $rawImage, "$uuid.jpg");
    }

    /**
     * @param $pickup
     * @param $optionValue
     * @param $gmapsKey
     * @return string
     * @throws \Siberian\Exception
     */
    public static function staticMapFromPickup($pickup, $optionValue, $gmapsKey)
    {
        $lat = $pickup["lat"];
        $lng = $pickup["lng"];

        $options = [
            "markers" => [
                "color:green|size:mid|{$lat},{$lng}",
            ],
            "size" => "600x220",
            "scale" => "2"
        ];

        $mapStaticUri = Geocoding::mapStatic($gmapsKey, $options);
        $rawImage = file_get_contents($mapStaticUri);
        $uuid = uniqid('cb_', true);

        return Feature::createFile($optionValue, $rawImage, "$uuid.jpg");
    }

    /**
     * @param $clientId
     * @param $vehicleType
     * @param $valueId
     * @param $drivers
     * @param $cashOrVault
     * @param $route
     * @param $staticMap
     * @param $customFormFields
     * @param $source
     * @return $this
     * @throws Exception
     * @throws \Zend_Currency_Exception
     * @throws \Zend_Exception
     */
    public function createRideRequest($clientId, $vehicleType, $valueId, $drivers, $cashOrVault, $route, $ride,
                                      $staticMap, $customFormFields, $source, $type = 'course', $seats = 1)
    {
        $travel = $route["request"];
        if ($type === 'course') {
            $leg = $route["routes"][0]["legs"][0];
            $distanceKm = ceil($leg["distance"]["value"] / 1000);
            $durationMinute = ceil($leg["duration"]["value"] / 60);

            $pickupAddress = $leg["start_address"];
            $pickupLatitude = $travel["origin"]["location"]["lat"];
            $pickupLongitude = $travel["origin"]["location"]["lat"];

            $dropoffAddress = $leg["start_address"];
            $dropoffLatitude = $travel["destination"]["location"]["lat"];
            $dropoffLongitude = $travel["destination"]["location"]["lat"];
        } else {
            $distanceKm = 0;
            $durationMinute = $ride['duration'];

            $pickupAddress = $ride['pickupAddress'];
            $pickupLatitude = $ride['pickup']['latitude'];
            $pickupLongitude = $ride['pickup']['longitude'];

            $dropoffAddress = '';
            $dropoffLatitude = 0;
            $dropoffLongitude = 0;
        }

        $cabride = (new Cabride)->find($valueId, "value_id");

        $lowestCost = null;
        $highestCost = null;
        foreach ($drivers as $driver) {
            $driverId = $driver["driver_id"];
            $_tmpDriver = (new Driver())->find($driverId);

            if ($type === 'course') {
                $pricing = $_tmpDriver->estimatePricing($distanceKm, $durationMinute, $seats);
            } else {
                $pricing = $_tmpDriver->estimatePricingTour($durationMinute, $seats);
            }
            $estimatedCost = $pricing['price'];

            if ($lowestCost === null) {
                $lowestCost = $estimatedCost;
            }

            if ($estimatedCost < $lowestCost) {
                $lowestCost = $estimatedCost;
            }

            if ($highestCost === null) {
                $highestCost = $estimatedCost;
            }

            if ($estimatedCost > $highestCost) {
                $highestCost = $estimatedCost;
            }
        }

        $this
            ->setValueId($valueId)
            ->setClientId($clientId)
            ->setType($type)
            ->setSeats($seats)
            ->setVehicleId($vehicleType->getId())
            ->setStaticImage($staticMap)
            ->setEstimatedCost($highestCost)
            ->setEstimatedLowestCost($lowestCost)
            ->setDistance($distanceKm * 1000)
            ->setDuration($durationMinute * 60)
            ->setFromAddress($pickupAddress)
            ->setFromLat($pickupLatitude)
            ->setFromLng($pickupLongitude)
            ->setToAddress($dropoffAddress)
            ->setToLat($dropoffLatitude)
            ->setToLng($dropoffLongitude)
            ->setRequestMode('immediate')
            ->setCustomFormFields($customFormFields)
            ->setRawRoute(Json::encode($route));

        if ($cashOrVault === "cash") {
            $this->setPaymentType("cash");
        } else {
            $this
                ->setPaymentType("credit-card")
                ->setClientVaultId($cashOrVault["vaultId"]);
        }

        // Drivers
        $now = time();
        $expires = $now + $cabride->getSearchTimeout();

        $this
            ->setRequestedAt($now)
            ->setExpiresAt($expires)
            ->save();

        $sentToDrivers = false;
        foreach ($drivers as $index => $driver) {
            $driverId = $driver["driver_id"];
            $_tmpDriver = (new Driver())->find($driverId);
            if ($_tmpDriver->getId()) {
                // Link & notify drivers
                $requestDriver = new RequestDriver();
                $requestDriver
                    ->setRequestId($this->getId())
                    ->setDriverId($driverId)
                    ->setStatus("pending")
                    ->setRequestedAt($now)
                    ->setExpiresAt($expires)
                    ->save();

                $sentToDrivers = true;
            }
        }

        if (!$sentToDrivers) {
            throw new Exception(p__("cabride",
                "We are sorry, but an error occurred while sending your request to the available drivers!"));
        }

        $this->changeStatus("pending", $source);

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
     * @param $clientId
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
        try {
            $this->notify($source);
        } catch (\Exception $e) {
            // Error on notify;
            echo $e->getMessage();
        }

        self::log($this, $statusFrom, $statusTo, $source);
    }

    /**
     * @param null $source
     * @throws \Zend_Exception
     */
    public function notify($source = null)
    {
        $valueId = $this->getValueId();
        $cabride = (new \Application_Model_Option_Value())->find($valueId);
        $application = (new \Application_Model_Application())->find($cabride->getAppId());
        $appId = $application->getId();
        $appKey = $application->getKey();
        $clientId = $this->getClientId();
        $requestId = $this->getRequestId();

        $status = $this->getStatus();

        switch ($status) {
            case "pending":
                // Notify all drivers found!
                $requestDrivers = (new RequestDriver())->findAll([
                    "request_id = ?" => $requestId,
                    "status = ?" => "pending"
                ]);

                // Send push to passenger!
                $title = p__("cabride",
                    "Ride request!");
                $message = p__("cabride",
                    "You have a new ride request!");
                $actionUrl = "/{$appKey}/cabride/mobile_pending_requests/index";

                foreach ($requestDrivers as $requestDriver) {

                    $driver = (new Driver())->find($requestDriver->getDriverId(), "driver_id");
                    $customerId = $driver->getCustomerId();
                    $pushDevice = (new PushDevice())
                        ->find($customerId, "customer_id");

                    if ($pushDevice && $pushDevice->getId()) {
                        $pushDevice->sendMessage($title, $message, $requestId, "driver",
                            $status, $actionUrl, $valueId, $appId);
                    }
                }

                break;
            case "accepted":
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

                if ($pushDevice->getId()) {
                    $pushDevice->sendMessage($title, $message, $requestId, "passenger",
                        $status, $actionUrl, $valueId, $appId);
                }
                break;
            case "onway":
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

                if ($pushDevice->getId()) {
                    $pushDevice->sendMessage($title, $message, $requestId, "passenger",
                        $status, $actionUrl, $valueId, $appId);
                }

                break;
            case "inprogress":

                break;
            case "declined":
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

                if ($pushDevice->getId()) {
                    $pushDevice->sendMessage($title, $message, $requestId, "passenger",
                        $status, $actionUrl, $valueId, $appId);
                }

                break;
            case "done":
                // Send push to passenger!
                $title = p__("cabride",
                    "Your ride is done!");
                $message = p__("cabride",
                    "Thanks for riding with us, hope to see you again soon!<br />Feel free to leave us a feedback in the rating section!");

                $actionUrl = "/{$appKey}/cabride/mobile_home/index";

                $client = (new Client())->find($clientId, "client_id");
                $customerId = $client->getCustomerId();
                $pushDevice = (new PushDevice())
                    ->find($customerId, "customer_id");

                if ($pushDevice->getId()) {
                    $pushDevice->sendMessage($title, $message, $requestId, "passenger",
                        $status, $actionUrl, $valueId, $appId);
                }

                break;
            case "aborted":
                // Send push to passenger!
                $title = p__("cabride",
                    "A ride request was cancelled!");
                $actionUrl = "/{$appKey}/cabride/mobile_home/index";

                // Client aborted AND a driver already accepted
                if ($source === Request::SOURCE_CLIENT &&
                    $this->getDriverId()) {

                    $message = p__("cabride",
                        "We are sorry, but the passenger cancelled the ride!");

                    $driver = (new Driver())->find($this->getDriverId(), "driver_id");
                    $customerId = $driver->getCustomerId();
                    $pushDevice = (new PushDevice())
                        ->find($customerId, "customer_id");

                    if ($pushDevice && $pushDevice->getId()) {
                        $pushDevice->sendMessage($title, $message, $requestId, "driver",
                            $status, $actionUrl, $valueId, $appId);
                    }
                }

                // Driver aborted
                if ($source === Request::SOURCE_DRIVER) {
                    $message = p__("cabride",
                        "We are sorry, but the driver cancelled the ride!");

                    $client = (new Client())->find($clientId, "client_id");
                    $customerId = $client->getCustomerId();
                    $pushDevice = (new PushDevice())
                        ->find($customerId, "customer_id");

                    if ($pushDevice && $pushDevice->getId()) {
                        $pushDevice->sendMessage($title, $message, $requestId, "passenger",
                            $status, $actionUrl, $valueId, $appId);
                    }
                }

                break;
            case "expired":
                // Send push to passenger!
                $title = p__("cabride",
                    "Your request expired!");
                $message = p__("cabride",
                    "Sorry there was no driver available to drive you, please try again!");

                $actionUrl = "/{$appKey}/cabride/mobile_home/index";

                $client = (new Client())->find($clientId, "client_id");
                $customerId = $client->getCustomerId();
                $pushDevice = (new PushDevice())
                    ->find($customerId, "customer_id");

                if ($pushDevice && $pushDevice->getId()) {
                    $pushDevice->sendMessage($title, $message, $requestId, "passenger",
                        $status, $actionUrl, $valueId, $appId);
                }

                break;
        }
    }

    /**
     * Notify the customer driver is here!
     *
     * @throws Exception
     * @throws \Zend_Exception
     */
    public function notifyCustomer()
    {
        $valueId = $this->getValueId();
        $cabride = (new \Application_Model_Option_Value())->find($valueId);
        $application = (new \Application_Model_Application())->find($cabride->getAppId());
        $appId = $application->getId();
        $appKey = $application->getKey();
        $clientId = $this->getClientId();
        $requestId = $this->getRequestId();

        // Send push to passenger!
        $title = p__('cabride',
            'Driver at pickup point!');
        $message = p__('cabride',
            'Your driver is arrived at the pickup point!');

        $actionUrl = "/{$appKey}/cabride/mobile_my_rides/index";

        $client = (new Client())->find($clientId, 'client_id');
        $customerId = $client->getCustomerId();
        $pushDevice = (new PushDevice())
            ->find($customerId, 'customer_id');

        if ($pushDevice && $pushDevice->getId()) {
            $pushDevice->sendMessage($title, $message, $requestId, 'passenger',
                'at_pickup', $actionUrl, $valueId, $appId);
        }
    }

    /**
     * @return array|mixed
     */
    public function getCustomFormFields()
    {
        $_tmp = $this->getData("custom_form_fields");
        try {
            $customFormFields = Json::decode(base64_decode($_tmp));
        } catch (\Exception $e) {
            $customFormFields = [];
        }
        return $customFormFields;
    }

    /**
     * @param $customFormFields
     * @return Request
     */
    public function setCustomFormFields($customFormFields)
    {
        $_tmp = base64_encode(Json::encode($customFormFields));
        return $this->setData("custom_form_fields", $_tmp);
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
        $now = time();

        $requestLog = new RequestLog();
        $requestLog
            ->setRequestId($request->getId())
            ->setValueId($request->getValueId())
            ->setStatusFrom($statusFrom)
            ->setStatusTo($statusTo)
            ->setSource($source)
            ->setSegmentHour(date("H", $now))
            ->setSegmentMinute(date("i", $now))
            ->setSegmentDay(date("d", $now))
            ->setSegmentMonth(date("m", $now))
            ->setSegmentYear(date("Y", $now))
            ->setTimestamp($now)
            ->save();
    }

    /**
     * @param \Cron_Model_Cron $cron
     * @throws \Zend_Exception
     */
    public static function toExpire($cron)
    {
        // Fetch all pending requests
        $pendingRequests = (new self())->fetchPending();
        foreach ($pendingRequests as $pendingRequest) {
            $data = $pendingRequest->getData();

            $now = time();
            $expireAt = $data["expires_at"];
            $id = $data["request_id"];

            if ($now > $expireAt) {
                $cron->log("[Cabride] now {$now} / {$expireAt}.");
                $cron->log("[Cabride] marking request_id {$id} as expired.");

                $pendingRequest->changeStatus("expired", self::SOURCE_CRON);
            }
        }
    }
}
