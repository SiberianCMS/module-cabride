<?php

use Cabride\Model\Client;
use Cabride\Model\Driver;
use Cabride\Model\Request;
use Cabride\Model\Vehicle;
use Cabride\Model\Cabride;
use Cabride\Model\RequestDriver;
use Core\Model\Base;
use Siberian\Json;

/**
 * Class Cabride_Mobile_RideController
 */
class Cabride_Mobile_RideController extends Application_Controller_Mobile_Default
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

            $client = (new Client())->find($customerId, "customer_id");
            $rides = (new Request())->findExtended($valueId, $client->getId());

            $collection = [];
            foreach ($rides as $ride) {
                $data = $ride->getData();

                // Makes payload lighter!
                unset($data["raw_route"]);

                $data["formatted_price"] = Base::_formatPrice($data["estimated_cost"]);

                // Recast values
                $data["search_timeout"] = (integer) $data["search_timeout"];
                $data["timestamp"] = (integer) $data["timestamp"];

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
    public function pendingAction ()
    {
        try {
            $request = $this->getRequest();
            $session = $this->getSession();
            $customerId = $session->getCustomerId();
            $optionValue = $this->getCurrentOptionValue();
            $valueId = $optionValue->getId();

            $driver = (new Driver())->find($customerId, "customer_id");
            $rides = (new Request())->findForDriver($valueId, $driver->getId(), "pending");

            $collection = [];
            foreach ($rides as $ride) {
                $data = $ride->getData();

                // Makes payload lighter!
                unset($data["raw_route"]);

                $data["formatted_price"] = Base::_formatPrice($data["estimated_cost"]);

                // Recast values
                $data["search_timeout"] = (integer) $data["search_timeout"];
                $data["timestamp"] = (integer) $data["timestamp"];

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

            $driver = (new Driver())->find($customerId, "customer_id");
            $rides = (new Request())->findForDriver($valueId, $driver->getId(), ["accepted", "onway", "inprogress"]);

            $collection = [];
            foreach ($rides as $ride) {
                $data = $ride->getData();

                // Makes payload lighter!
                unset($data["raw_route"]);

                $data["formatted_price"] = Base::_formatPrice($data["estimated_cost"]);

                // Recast values
                $data["search_timeout"] = (integer) $data["search_timeout"];
                $data["timestamp"] = (integer) $data["timestamp"];

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

            $driver = (new Driver())->find($customerId, "customer_id");
            $rides = (new Request())->findForDriver($valueId, $driver->getId(), "done");

            $collection = [];
            foreach ($rides as $ride) {
                $data = $ride->getData();

                // Makes payload lighter!
                unset($data["raw_route"]);

                $data["formatted_price"] = Base::_formatPrice($data["estimated_cost"]);

                // Recast values
                $data["search_timeout"] = (integer) $data["search_timeout"];
                $data["timestamp"] = (integer) $data["timestamp"];

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

            $driver = (new Driver())->find($customerId, "customer_id");
            $rides = (new Request())->findForDriver($valueId, $driver->getId(), "declined");

            $collection = [];
            foreach ($rides as $ride) {
                $data = $ride->getData();

                // Makes payload lighter!
                unset($data["raw_route"]);

                $data["formatted_price"] = Base::_formatPrice($data["estimated_cost"]);

                // Recast values
                $data["search_timeout"] = (integer) $data["search_timeout"];
                $data["timestamp"] = (integer) $data["timestamp"];

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
            $route = $request->getParam("route", false);
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

                $data["id"] = $data["vehicle_id"];
                $data["label"] = $data["type"];
                $data["baseFare"] = Base::_formatPrice($data["base_fare"]);
                $data["distanceFare"] = ($data["distance_fare"] > 0) ?
                    Base::_formatPrice($data["distance_fare"]) : 0;
                $data["timeFare"] = ($data["time_fare"] > 0) ?
                    Base::_formatPrice($data["time_fare"]) : 0;

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
            $currentType["baseFare"] = Base::_formatPrice($currentType["base_fare"]);
            $currentType["distanceFare"] = ($currentType["distance_fare"] > 0) ?
                Base::_formatPrice($currentType["distance_fare"]) : 0;
            $currentType["timeFare"] = ($currentType["time_fare"] > 0) ?
                Base::_formatPrice($currentType["time_fare"]) : 0;

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

            if (empty($driverParams["driver_license"]) || empty($driverParams["vehicle_license_plate"])) {
                throw new Exception(p__("cabride",
                    "Both your driving license & vehicle license plate are required!"));
            }

            $driver
                ->setVehicleModel($driverParams["vehicle_model"])
                ->setVehicleLicensePlate($driverParams["vehicle_license_plate"])
                ->setDriverLicense($driverParams["driver_license"])
                ->setBaseAddress($driverParams["base_address"])
                ->setPickupRadius($driverParams["pickup_radius"]);

            if ($cabride->getPricingMode() === "driver") {
                if ($driverParams["base_fare"] <= 0 &&
                    ($driverParams["distance_fare"] <= 0 || $driverParams["time_fare"] <= 0)) {
                    throw new Exception(p__("cabride", "Driving fares are required!"));
                }

                if (empty($driverParams["base_fare"]) &&
                    (empty($driverParams["distance_fare"]) || empty($driverParams["time_fare"]))) {
                    throw new Exception(p__("cabride", "Driving fares are required!"));
                }

                $driver
                    ->setBaseFare($driverParams["base_fare"])
                    ->setDistanceFare($driverParams["distance_fare"])
                    ->setTimeFare($driverParams["time_fare"]);
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
                ->setStatus("onway")
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
}
