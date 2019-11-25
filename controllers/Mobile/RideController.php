<?php

use Cabride\Model\Client;
use Cabride\Model\Driver;
use Cabride\Model\Request;
use Cabride\Model\Vehicle;
use Cabride\Model\Payment;
use Cabride\Model\Cabride;
use Cabride\Model\RequestDriver;
use Cabride\Model\ClientVault;
use Siberian\Currency;
use Core\Model\Base;
use Siberian\Json;
use Siberian_Google_Geocoding as Geocoding;
use Cabride\Controller\Mobile as MobileController;

use Stripe\Stripe;
use Stripe\PaymentIntent;

/**
 * Class Cabride_Mobile_RideController
 */
class Cabride_Mobile_RideController extends MobileController
{
    /**
     * Client route
     */
    public function meAction()
    {
        try {
            $request = $this->getRequest();
            $session = $this->getSession();
            $customerId = $session->getCustomerId();
            $optionValue = $this->getCurrentOptionValue();
            $valueId = $optionValue->getId();

            $cabride = (new Cabride())->find($valueId, "value_id");
            $client = (new Client())->find($customerId, "customer_id");
            $rides = (new Request())->findExtended($valueId, $client->getId());

            $collection = [];
            foreach ($rides as $ride) {
                $data = $ride->getData();

                // Makes payload lighter!
                unset($data["raw_route"]);

                $data["formatted_price"] = Base::_formatPrice($data["estimated_cost"], $cabride->getCurrency());
                $data["formatted_lowest_price"] = Base::_formatPrice($data["estimated_lowest_cost"], $cabride->getCurrency());

                $data["formatted_driver_price"] = false;
                if (!empty($data["driver_id"])) {
                    $driver = (new Driver())->find($data["driver_id"]);
                    $distanceKm = ceil($ride->getDistance() / 1000);
                    $durationMinute = ceil($ride->getDuration() / 60);
                    $driverPrice = $driver->estimatePricing($distanceKm, $durationMinute, false);

                    $data["formatted_driver_price"] = Base::_formatPrice($driverPrice, $cabride->getCurrency());
                    $data["driver_phone"] = $driver->getDriverPhone();

                    // Driver request!
                    $driverRequest = (new RequestDriver())->find([
                        "driver_id" => $driver->getId(),
                        "request_id" => $ride->getId(),
                    ]);

                    if ($driverRequest->getId()) {
                        $data["eta_driver"] = (integer) $driverRequest->getEtaToClient();
                    }
                }

                // Recast values
                $now = time();
                $data["search_timeout"] = (integer) $data["search_timeout"];
                $data["timestamp"] = (integer) $data["timestamp"];
                $data["expires_in"] = (integer) ($data["expires_at"] - $now);

                $collection[] = $data;
            }

            $payload = [
                "success" => true,
                "collection" => $collection,
            ];
        } catch (\Exception $e) {
            $payload = [
                "error" => true,
                "message" => __("An unknown error occurred, please try again later."),
                "except" => $e->getMessage()
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     * Client route
     */
    public function myPaymentsAction()
    {
        try {
            $request = $this->getRequest();
            $session = $this->getSession();
            $customerId = $session->getCustomerId();
            $optionValue = $this->getCurrentOptionValue();
            $valueId = $optionValue->getId();

            $cabride = (new Cabride())->find($valueId, "value_id");
            $client = (new Client())->find($customerId, "customer_id");

            $payments = (new Payment())->fetchForClientId($client->getId());

            $cards = (new ClientVault())->findAll([
                "client_id = ?" => $client->getId(),
                "payment_provider = ?" => $cabride->getPaymentProvider(),
                "is_removed = ?" => 0,
            ]);

            $paymentData = [];
            foreach ($payments as $payment) {
                $data = $payment->getData();

                $data["formatted_amount"] = Base::_formatPrice($data["amount"], $data["currency"]);

                $paymentData[] = $data;
            }

            $cardData = [];
            foreach ($cards as $card) {
                // Just in case, we skip "card_xxx" old vaults!
                if (!empty($card->getCardToken())) {
                    continue;
                }

                $data = $card->getData();

                unset($data["raw_payload"]);

                $cardData[] = $data;
            }

            $payload = [
                "success" => true,
                "payments" => $paymentData,
                "cards" => $cardData,
            ];
        } catch (\Exception $e) {
            $payload = [
                "error" => true,
                "message" => __("An unknown error occurred, please try again later."),
                "except" => $e->getMessage()
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     * Client route cancel
     */
    public function cancelAction ()
    {
        try {
            $request = $this->getRequest();
            $session = $this->getSession();
            $customerId = $session->getCustomerId();
            $optionValue = $this->getCurrentOptionValue();
            $valueId = $optionValue->getId();
            $requestId = $request->getParam("requestId", false);

            $cancelReason = $request->getBodyParams();

            $ride = (new Request())->find($requestId);

            if (!$requestId || !$ride->getId()) {
                throw new Exception(p__("cabride",
                    "Sorry, we are unable to find this ride request!"));
            }

            /**
             * @var $requestDrivers RequestDriver[]
             */
            $requestDrivers = (new RequestDriver())->findAll([
                "request_id" => $requestId,
            ]);

            foreach ($requestDrivers as $requestDriver) {
                $requestDriver->setStatus("aborted")->save();
            }

            $ride
                ->setCancelReason($cancelReason["reason"])
                ->setCancelNote($cancelReason["message"])
                ->save();

            $ride->cancelAuthorization();
            $ride->changeStatus("aborted", Request::SOURCE_CLIENT);

            $payload = [
                "success" => true,
                "message" => p__("cabride", "Your request is cancelled!"),
            ];
        } catch (\Exception $e) {
            $payload = [
                "error" => true,
                "message" => $e->getMessage(),
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     * Driver route cancel
     */
    public function cancelDriverAction ()
    {
        try {
            $request = $this->getRequest();
            $session = $this->getSession();
            $customerId = $session->getCustomerId();
            $optionValue = $this->getCurrentOptionValue();
            $valueId = $optionValue->getId();
            $requestId = $request->getParam("requestId", false);

            $cancelReason = $request->getBodyParams();

            $ride = (new Request())->find($requestId);

            if (!$requestId || !$ride->getId()) {
                throw new Exception(p__("cabride",
                    "Sorry, we are unable to find this ride request!"));
            }

            /**
             * @var $requestDrivers RequestDriver[]
             */
            $requestDrivers = (new RequestDriver())->findAll([
                "request_id" => $requestId,
            ]);

            foreach ($requestDrivers as $requestDriver) {
                $requestDriver->setStatus("aborted")->save();
            }

            $ride
                ->setCancelReason($cancelReason["reason"])
                ->setCancelNote($cancelReason["message"])
                ->save();

            $ride->cancelAuthorization();
            $ride->changeStatus("aborted", Request::SOURCE_DRIVER);

            $payload = [
                "success" => true,
                "message" => p__("cabride", "Your request is cancelled!"),
            ];
        } catch (\Exception $e) {
            $payload = [
                "error" => true,
                "message" => $e->getMessage(),
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     * Driver route
     */
    public function pendingAction ()
    {
        try {
            $request = $this->getRequest();
            $session = $this->getSession();
            $customerId = $session->getCustomerId();
            $optionValue = $this->getCurrentOptionValue();
            $valueId = $optionValue->getId();

            $cabride = (new Cabride())->find($valueId, "value_id");
            $driver = (new Driver())->find($customerId, "customer_id");
            $rides = (new Request())->findForDriver($valueId, $driver->getId(), "pending");

            $collection = [];
            foreach ($rides as $ride) {
                $data = $ride->getData();

                // Makes payload lighter!
                unset($data["raw_route"]);

                $data["formatted_price"] = Base::_formatPrice($data["estimated_cost"], $cabride->getCurrency());

                // Recast values
                $now = time();
                $data["search_timeout"] = (integer) $data["search_timeout"];
                $data["timestamp"] = (integer) $data["timestamp"];
                $data["expires_in"] = (integer) ($data["expires_at"] - $now);

                $collection[] = $data;
            }

            $payload = [
                "success" => true,
                "collection" => $collection,
            ];
        } catch (\Exception $e) {
            $payload = [
                "error" => true,
                "message" => __("An unknown error occurred, please try again later."),
                "except" => $e->getMessage()
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     * Driver route
     */
    public function acceptedAction ()
    {
        try {
            $request = $this->getRequest();
            $session = $this->getSession();
            $customerId = $session->getCustomerId();
            $optionValue = $this->getCurrentOptionValue();
            $valueId = $optionValue->getId();

            $cabride = (new Cabride())->find($valueId, "value_id");
            $driver = (new Driver())->find($customerId, "customer_id");
            $rides = (new Request())->findForDriver($valueId, $driver->getId(), ["accepted", "onway", "inprogress"]);

            $collection = [];
            foreach ($rides as $ride) {
                $data = $ride->getData();

                // Makes payload lighter!
                unset($data["raw_route"]);

                $data["formatted_price"] = Base::_formatPrice($data["cost"], $cabride->getCurrency());

                // Recast values
                $now = time();
                $data["search_timeout"] = (integer) $data["search_timeout"];
                $data["timestamp"] = (integer) $data["timestamp"];
                $data["expires_in"] = (integer) ($data["expires_at"] - $now);

                $client = (new Client())->find($ride->getClientId());

                $data["client_phone"] = $client->getMobile();

                $collection[] = $data;
            }

            $payload = [
                "success" => true,
                "collection" => $collection,
            ];
        } catch (\Exception $e) {
            $payload = [
                "error" => true,
                "message" => __("An unknown error occurred, please try again later."),
                "except" => $e->getMessage()
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     * Driver route
     */
    public function completedAction ()
    {
        try {
            $request = $this->getRequest();
            $session = $this->getSession();
            $customerId = $session->getCustomerId();
            $optionValue = $this->getCurrentOptionValue();
            $valueId = $optionValue->getId();

            $cabride = (new Cabride())->find($valueId, "value_id");
            $driver = (new Driver())->find($customerId, "customer_id");
            $rides = (new Request())->findForDriver($valueId, $driver->getId(), "done");

            $collection = [];
            foreach ($rides as $ride) {
                $data = $ride->getData();

                // Makes payload lighter!
                unset($data["raw_route"]);

                $data["formatted_price"] = Base::_formatPrice($data["cost"], $cabride->getCurrency());

                // Recast values
                $now = time();
                $data["search_timeout"] = (integer) $data["search_timeout"];
                $data["timestamp"] = (integer) $data["timestamp"];
                $data["expires_in"] = (integer) ($data["expires_at"] - $now);

                $collection[] = $data;
            }

            $payload = [
                "success" => true,
                "collection" => $collection,
            ];
        } catch (\Exception $e) {
            $payload = [
                "error" => true,
                "message" => __("An unknown error occurred, please try again later."),
                "except" => $e->getMessage()
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     * Driver route
     */
    public function cancelledAction ()
    {
        try {
            $request = $this->getRequest();
            $session = $this->getSession();
            $customerId = $session->getCustomerId();
            $optionValue = $this->getCurrentOptionValue();
            $valueId = $optionValue->getId();

            $cabride = (new Cabride())->find($valueId, "value_id");
            $driver = (new Driver())->find($customerId, "customer_id");
            $rides = (new Request())->findForDriver($valueId, $driver->getId(), "declined");

            $collection = [];
            foreach ($rides as $ride) {
                $data = $ride->getData();

                // Makes payload lighter!
                unset($data["raw_route"]);

                $data["formatted_price"] = Base::_formatPrice($data["estimated_cost"], $cabride->getCurrency());

                // Recast values
                $now = time();
                $data["search_timeout"] = (integer) $data["search_timeout"];
                $data["timestamp"] = (integer) $data["timestamp"];
                $data["expires_in"] = (integer) ($data["expires_at"] - $now);

                $collection[] = $data;
            }

            $payload = [
                "success" => true,
                "collection" => $collection,
            ];
        } catch (\Exception $e) {
            $payload = [
                "error" => true,
                "message" => __("An unknown error occurred, please try again later."),
                "except" => $e->getMessage()
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     * Driver route
     */
    public function declineAction ()
    {
        try {
            $request = $this->getRequest();
            $session = $this->getSession();
            $customerId = $session->getCustomerId();
            $optionValue = $this->getCurrentOptionValue();
            $valueId = $optionValue->getId();
            $requestId = $request->getParam("requestId", false);

            $cabride = (new Cabride())->find($valueId, "value_id");
            $ride = (new Request())->find($requestId);

            if (!$requestId || !$ride->getId()) {
                throw new Exception(p__("cabride",
                    "Sorry, we are unable to find this ride request!"));
            }

            $driver = (new Driver())->find($customerId, "customer_id");

            $requestDriver = (new RequestDriver())->find([
                "request_id" => $requestId,
                "driver_id" => $driver->getId(),
                "status" => "pending"
            ]);

            if ($requestDriver->getId()) {
                $requestDriver
                    ->setStatus("declined")
                    ->save();
            }

            $payload = [
                "success" => true,
                "message" => p__("cabride", "You declined the request!"),
            ];
        } catch (\Exception $e) {
            $payload = [
                "error" => true,
                "message" => $e->getMessage(),
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     * Driver route
     */
    public function acceptAction ()
    {
        try {
            $request = $this->getRequest();
            $session = $this->getSession();
            $customerId = $session->getCustomerId();
            $optionValue = $this->getCurrentOptionValue();
            $valueId = $optionValue->getId();
            $requestId = $request->getParam("requestId", false);
            $data = $request->getBodyParams();
            $route = $data["route"];

            $cabride = (new Cabride())->find($valueId, "value_id");
            $ride = (new Request())->find($requestId);

            if (!$requestId || !$ride->getId()) {
                throw new Exception(p__("cabride",
                    "Sorry, we are unable to find this ride request!"));
            }

            $driver = (new Driver())->find($customerId, "customer_id");

            $requestDriver = (new RequestDriver())->find([
                "request_id" => $requestId,
                "driver_id" => $driver->getId(),
                "status" => ["pending", "declined"]
            ]);

            if (!$requestDriver->getId()) {
                throw new Exception(p__("cabride",
                    "Sorry, we are unable to find this ride request!"));
            }

            $requestDriver
                ->setRawRoute(Json::encode($route))
                ->setStatus("accepted")
                ->save();

            $distanceKm = ceil($ride->getDistance() / 1000);
            $durationMinute = ceil($ride->getDuration() / 60);
            $driverPrice = $driver->estimatePricing($distanceKm, $durationMinute, false);
            $ride->setCost($driverPrice);

            $ride->changeStatus("accepted", Request::SOURCE_DRIVER);

            $ride
                ->setDriverId($driver->getId())
                ->save();

            // So also expires all other drivers!
            $requestDrivers = (new RequestDriver())
                ->findAll(["request_id = ?" => $requestId, "driver_id != ?" => $driver->getId()]);
            foreach ($requestDrivers as $requestDriver) {
                $requestDriver
                    ->setStatus("accepted_other")
                    ->save();
            }

            $payload = [
                "success" => true,
                "message" => p__("cabride", "You finally accepted the request!"),
            ];
        } catch (\Exception $e) {
            $payload = [
                "error" => true,
                "message" => $e->getMessage(),
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     * Driver route
     */
    public function vehicleInformationAction ()
    {
        try {
            $request = $this->getRequest();
            $session = $this->getSession();
            $customerId = $session->getCustomerId();
            $optionValue = $this->getCurrentOptionValue();
            $valueId = $optionValue->getId();
            $requestId = $request->getParam("requestId", false);

            $cabride = (new Cabride())->find($valueId, "value_id");
            $driver = (new Driver())->find($customerId, "customer_id");

            if (!$driver->getId()) {
                throw new Exception(p__("cabride",
                    "Sorry, we are unable to find your driver profile!"));
            }

            // So also expires all other drivers!
            $vehicleTypes = (new Vehicle())
                ->findAll([
                    "value_id = ?" => $valueId,
                    "is_visible = ?" => 1
                ]);

            $driverData = $driver->toJson();

            $types = [];
            $currentType = null;
            foreach ($vehicleTypes as $vehicleType) {
                $data = $vehicleType->getData();

                dbg($data);
                dbg($driverData["vehicle_id"]);

                $data["id"] = $data["vehicle_id"];
                $data["label"] = $data["type"];
                $data["baseFare"] = Base::_formatPrice($data["base_fare"], $cabride->getCurrency());
                $data["distanceFare"] = ($data["distance_fare"] > 0) ?
                    Base::_formatPrice($data["distance_fare"], $cabride->getCurrency()) : 0;
                $data["timeFare"] = ($data["time_fare"] > 0) ?
                    Base::_formatPrice($data["time_fare"], $cabride->getCurrency()) : 0;

                $types[] = $data;

                if ($driverData["hasVehicle"] && $data["id"] == $driverData["vehicle_id"]) {
                    $currentType = $data;
                }
            }

            $payload = [
                "success" => true,
                "driver" => $driverData,
                "vehicleTypes" => $types,
                "currentType" => $currentType,
                "message" => p__("cabride", "You finally accepted the request!"),
            ];
        } catch (\Exception $e) {
            $payload = [
                "error" => true,
                "message" => $e->getMessage(),
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     * Driver route
     */
    public function selectVehicleTypeAction ()
    {
        try {
            $request = $this->getRequest();
            $session = $this->getSession();
            $customerId = $session->getCustomerId();
            $typeId = $request->getParam("typeId", false);
            $optionValue = $this->getCurrentOptionValue();
            $valueId = $optionValue->getId();

            $cabride = (new Cabride())->find($valueId, "value_id");
            $driver = (new Driver())->find($customerId, "customer_id");

            if (!$driver->getId()) {
                throw new Exception(p__("cabride",
                    "Sorry, we are unable to find your driver profile!"));
            }

            // If the vehicle type is different, alert the admin!
            $previousVehicleId = $driver->getVehicleId();

            if ($previousVehicleId != $typeId) {
                // Send an e-mail to the App admin!
                // @todo
            }

            $driver
                ->setVehicleId($typeId)
                ->save();

            $type = (new Vehicle())
                ->find($typeId);

            $currentType = $type->getData();

            $currentType["id"] = $currentType["vehicle_id"];
            $currentType["label"] = $currentType["type"];
            $currentType["baseFare"] = Base::_formatPrice($currentType["base_fare"], $cabride->getCurrency());
            $currentType["distanceFare"] = ($currentType["distance_fare"] > 0) ?
                Base::_formatPrice($currentType["distance_fare"], $cabride->getCurrency()) : 0;
            $currentType["timeFare"] = ($currentType["time_fare"] > 0) ?
                Base::_formatPrice($currentType["time_fare"], $cabride->getCurrency()) : 0;

            $driverData = $driver->toJson();

            $payload = [
                "success" => true,
                "driver" => $driverData,
                "currentType" => $currentType,
                "message" => p__("cabride", "Success!"),
            ];
        } catch (\Exception $e) {
            $payload = [
                "error" => true,
                "message" => $e->getMessage(),
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     * Driver route
     */
    public function saveDriverAction ()
    {
        try {
            $request = $this->getRequest();
            $application = $this->getApplication();
            $session = $this->getSession();
            $data = $request->getBodyParams();
            $driverParams = $data["driver"];
            $valueId = Cabride::getCurrentValueId();

            $cabride = (new Cabride())->find($valueId, "value_id");
            $driver = (new Driver())->find($driverParams["driver_id"]);

            if (!$driver->getId()) {
                throw new Exception(p__("cabride",
                    "Sorry, we are unable to find your driver profile!"));
            }

            $errors = [];
            if (empty($driverParams["driver_license"])) {
                $errors[] = p__("cabride", "Driver license");
            }

            if (empty($driverParams["vehicle_license_plate"])) {
                $errors[] = p__("cabride", "License plate");
            }

            if (empty($driverParams["driver_phone"])) {
                $errors[] = p__("cabride", "Mobile number");
            }

            // Geocoding base address
            $position = Geocoding::getLatLng(
                ["address" => $driverParams["base_address"]],
                $application->getGooglemapsKey());

            if (empty($position[0]) || empty($position[1])) {
                $errors[] = p__("cabride", "Invalid address!");
            }

            if ($cabride->getPricingMode() === "driver") {
                if ($driverParams["base_fare"] <= 0 &&
                    ($driverParams["distance_fare"] <= 0 || $driverParams["time_fare"] <= 0)) {
                    $errors[] = p__("cabride", "Driving fares!");
                }

                if (empty($driverParams["base_fare"]) &&
                    (empty($driverParams["distance_fare"]) || empty($driverParams["time_fare"]))) {
                    $errors[] = p__("cabride", "Driving fares!");
                }

                $driver
                    ->setBaseFare($driverParams["base_fare"])
                    ->setDistanceFare($driverParams["distance_fare"])
                    ->setTimeFare($driverParams["time_fare"]);
            }

            $driver
                ->setVehicleId($driverParams["vehicle_id"])
                ->setVehicleModel($driverParams["vehicle_model"])
                ->setVehicleLicensePlate($driverParams["vehicle_license_plate"])
                ->setDriverPhone($driverParams["driver_phone"])
                ->setDriverLicense($driverParams["driver_license"])
                ->setBaseAddress($driverParams["base_address"])
                ->setBaseLatitude($position[0])
                ->setBaseLongitude($position[1])
                ->setPickupRadius($driverParams["pickup_radius"]);

            if (sizeof($errors) > 0) {
                foreach ($errors as &$error) {
                    $error = "- {$error}";
                }
                throw new Exception(join("<br />", $errors));
            }

            $driver->save();

            $driverData = $driver->toJson();

            $payload = [
                "success" => true,
                "driver" => $driverData,
                "message" => p__("cabride", "You vehicle information are saved!"),
            ];
        } catch (\Exception $e) {
            $payload = [
                "error" => true,
                "message" => $e->getMessage(),
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     * Driver route
     */
    public function driveToPassengerAction ()
    {
        try {
            $request = $this->getRequest();
            $session = $this->getSession();
            $customerId = $session->getCustomerId();
            $optionValue = $this->getCurrentOptionValue();
            $valueId = $optionValue->getId();
            $requestId = $request->getParam("requestId", false);

            $data = $request->getBodyParams();
            $route = $data["route"];

            $cabride = (new Cabride())->find($valueId, "value_id");
            $ride = (new Request())->find($requestId);

            if (!$requestId || !$ride->getId()) {
                throw new Exception(p__("cabride",
                    "Sorry, we are unable to find this ride request!"));
            }

            $driver = (new Driver())->find($customerId, "customer_id");

            $requestDriver = (new RequestDriver())->find([
                "request_id" => $requestId,
                "driver_id" => $driver->getId(),
                "status" => ["accepted", "onway"]
            ]);

            if (!$requestDriver->getId()) {
                throw new Exception(p__("cabride",
                    "Sorry, we are unable to find this ride request!"));
            }

            $timeToClient = (integer) $route["routes"][0]["legs"][0]["duration"]["value"];
            $timeToDestination =
                (integer) $route["routes"][0]["legs"][0]["duration"]["value"] +
                (integer) $route["routes"][0]["legs"][1]["duration"]["value"];

            $requestDriver
                ->setStatus("onway")
                ->setEtaToClient($timeToClient + time())
                ->setEtaToDestination($timeToDestination + time())
                ->setTimeToClient($timeToClient)
                ->setTimeToDestination($timeToDestination)
                ->save();

            $ride->changeStatus("onway", Request::SOURCE_DRIVER);

            $payload = [
                "success" => true,
                "driveTo" => [
                    "lat" => (float) $ride->getFromLat(),
                    "lng" => (float) $ride->getFromLng(),
                ],
                "message" => p__("cabride", "We notified your passenger that you are on his way!"),
            ];
        } catch (\Exception $e) {
            $payload = [
                "error" => true,
                "message" => $e->getMessage(),
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     * Driver route
     */
    public function driveToDestinationAction ()
    {
        try {
            $request = $this->getRequest();
            $session = $this->getSession();
            $customerId = $session->getCustomerId();
            $optionValue = $this->getCurrentOptionValue();
            $valueId = $optionValue->getId();
            $requestId = $request->getParam("requestId", false);

            $cabride = (new Cabride())->find($valueId, "value_id");
            $ride = (new Request())->find($requestId);

            if (!$requestId || !$ride->getId()) {
                throw new Exception(p__("cabride",
                    "Sorry, we are unable to find this ride request!"));
            }

            $driver = (new Driver())->find($customerId, "customer_id");

            $requestDriver = (new RequestDriver())->find([
                "request_id" => $requestId,
                "driver_id" => $driver->getId(),
                "status" => ["accepted", "onway"]
            ]);

            if (!$requestDriver->getId()) {
                throw new Exception(p__("cabride",
                    "Sorry, we are unable to find this ride request!"));
            }

            $requestDriver
                ->setStatus("inprogress")
                ->save();

            $ride->changeStatus("inprogress", Request::SOURCE_DRIVER);

            $payload = [
                "success" => true,
                "driveTo" => [
                    "lat" => (float) $ride->getToLat(),
                    "lng" => (float) $ride->getToLng(),
                ],
                "message" => p__("cabride", "Opening navigation!"),
            ];
        } catch (\Exception $e) {
            $payload = [
                "error" => true,
                "message" => $e->getMessage(),
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     * Driver route
     */
    public function completeAction ()
    {
        try {
            $application = $this->getApplication();
            $request = $this->getRequest();
            $session = $this->getSession();
            $customerId = $session->getCustomerId();
            $optionValue = $this->getCurrentOptionValue();
            $valueId = $optionValue->getId();
            $requestId = $request->getParam("requestId", false);

            $cabride = (new Cabride())->find($valueId, "value_id");
            $ride = (new Request())->find($requestId);

            if (!$requestId || !$ride->getId()) {
                throw new Exception(p__("cabride",
                    "Sorry, we are unable to find this ride request!"));
            }

            $clientId = $ride->getClientId();
            $client = (new Client())->find($clientId);
            $driver = (new Driver())->find($customerId, "customer_id");

            $requestDriver = (new RequestDriver())->find([
                "request_id" => $requestId,
                "driver_id" => $driver->getId(),
                "status" => "inprogress"
            ]);

            if (!$requestDriver->getId()) {
                throw new Exception(p__("cabride",
                    "Sorry, we are unable to find this ride request!"));
            }

            $driver = (new Driver())->find($ride->getDriverId());

            $distanceKm = ceil($ride->getDistance() / 1000);
            $durationMinute = ceil($ride->getDuration() / 60);
            $driverPrice = $driver->estimatePricing($distanceKm, $durationMinute, false);

            $requestDriver
                ->setStatus("done")
                ->save();

            $ride->setCost($driverPrice);
            $ride->changeStatus("done", Request::SOURCE_DRIVER);

            $charge = null;
            $status = "paid";

            $stripeCost = round($ride->getCost());

            // zero-decimals stripe currencies ....
            if (!in_array($cabride->getCurrency(), Currency::$zeroDecimals)) {
                $stripeCost = round($ride->getCost() * 100);
            }

            // Fetch the payment
            $payment = (new Payment())->find($ride>getId(), "request_id");

            // We will now just capture the "real amount" (we authorized up to the max of range)
            if ($ride->getPaymentType() === "credit-card") {
                Stripe::setApiKey($cabride->getStripeSecretKey());

                $intent = PaymentIntent::retrieve($payment->getStripePaymentIntent());
                $intent->capture([
                    "amount_to_capture" => $stripeCost
                ]);

                if ($intent["status"] === "succeeded") {
                    $status = "paid";
                } else {
                    $status = "unpaid";
                }
            }

            $payment
                ->setValueId($valueId)
                ->setRequestId($ride->getId())
                ->setDriverId($driver->getId())
                ->setClientId($client->getId())
                ->setClientVaultId($ride->getClientVaultId())
                ->setAmount($ride->getCost())
                ->setAmountCaptured($ride->getCost())
                ->setAmountCapturedIntent($stripeCost)
                ->setStatus($status)
                ->save();

            $payment->addCommission();

            $payload = [
                "success" => true,
                "message" => p__("cabride", "The course is now marked are complete!"),
            ];
        } catch (\Exception $e) {
            $payload = [
                "error" => true,
                "message" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     * Rate the ride
     */
    public function rateCourseAction ()
    {
        try {
            $request = $this->getRequest();
            $requestId = $request->getParam("requestId", false);
            $data = $request->getBodyParams();
            $ride = (new Request())->find($requestId);

            if (!$requestId || !$ride->getId()) {
                throw new Exception(p__("cabride",
                    "Sorry, we are unable to find this ride request!"));
            }

            $ride
                ->setCourseRating($data["rating"]["course"])
                ->setCourseComment($data["rating"]["comment"])
                ->save();

            $payload = [
                "success" => true,
                "message" => p__("cabride", "Thanks!"),
            ];
        } catch (\Exception $e) {
            $payload = [
                "error" => true,
                "message" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ];
        }

        $this->_sendJson($payload);
    }
}
