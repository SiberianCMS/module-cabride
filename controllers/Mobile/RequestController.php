<?php

use Cabride\Model\Cabride;
use Cabride\Model\Driver;
use Cabride\Model\Client;
use Cabride\Model\ClientVault;
use Cabride\Model\Request;
use Cabride\Model\RequestDriver;
use Cabride\Model\RequestLog;
use Cabride\Model\Vehicle;
use Core\Model\Base;
use Customer_Model_Customer as Customer;
use Siberian\Exception;
use Siberian_Google_Geocoding as Geocoding;

/**
 * Class Cabride_Mobile_RequestController
 */
class Cabride_Mobile_RequestController extends Application_Controller_Mobile_Default
{
    /**
     *
     */
    public function rideAction()
    {
        try {
            $request = $this->getRequest();
            $data = $request->getBodyParams();
            $optionValue = $this->getCurrentOptionValue();
            $session = $this->getSession();
            $customerId = $session->getCustomerId();

            $valueId = $optionValue->getId();
            $route = $data["route"];
            $request = $route["request"];
            $origin = $request["origin"]["location"];
            $lat = $origin["lat"];
            $lng = $origin["lng"];

            $distanceKm = ceil($route["routes"][0]["legs"][0]["distance"]["value"] / 1000);
            $durationMinute = ceil($route["routes"][0]["legs"][0]["duration"]["value"] / 60);

            // Searching for closest drivers!
            // Attention, distance is computed by the fly!
            $formula = Geocoding::getDistanceFormula($lat, $lng, "d", "latitude", "longitude");

            $drivers = (new Driver())
                ->findNearestOnline($valueId, $formula);

            $client = (new Client())->find($customerId, "customer_id");
            $cabride = (new Cabride())->find($optionValue->getId(), "value_id");

            $collection = [];
            foreach ($drivers as $driver) {

                $_tmpDriver = (new Driver())->find($driver->getId());

                $vehicleId = $_tmpDriver->getVehicleId();
                $pricing = $_tmpDriver->estimatePricing($distanceKm, $durationMinute);
                $pricingValue = $_tmpDriver->estimatePricing($distanceKm, $durationMinute, false);

                if (!array_key_exists($vehicleId, $collection)) {
                    $vehicle = (new Vehicle())->find($vehicleId);
                    $collection[$vehicleId] = [
                        "drivers" => [],
                        "id" => $vehicle->getId(),
                        "type" => $vehicle->getType(),
                        "icon" => $vehicle->getIcon(),
                        "pricing" => $pricing,
                        "pricingValue" => $pricingValue,
                        "lowPricing" => $pricing,
                        "lowPricingValue" => $pricingValue,
                        "prices" => [],
                    ];
                } else {
                    if ($pricingValue > $collection[$vehicleId]["pricingValue"]) {
                        // Gives highest estimate to passenger!
                        $collection[$vehicleId]["pricing"] = $pricing;
                        $collection[$vehicleId]["pricingValue"] = $pricingValue;
                    }

                    if ($pricingValue < $collection[$vehicleId]["lowPricingValue"]) {
                        // Lowest estimate to passenger!
                        $collection[$vehicleId]["lowPricing"] = $pricing;
                        $collection[$vehicleId]["lowPricingValue"] = $pricingValue;
                    }
                }

                $collection[$vehicleId]["drivers"][] = $_tmpDriver->getFilteredData();
            }

            $type = $cabride->getPaymentProvider();
            $clientVaults = (new ClientVault())->fetchForClientId($client->getId());
            $vaults = [];
            foreach ($clientVaults as $clientVault) {
                // Filter vaults by type!
                if ($clientVault->getPaymentProvider() === $type) {
                    $data = $clientVault->toJson();
                    $vaults[] = $data;
                }
            }

            $payload = [
                "success" => true,
                "collection" => $collection,
                "vaults" => $vaults,
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
     *
     */
    public function validateAction () 
    {
        try {
            $application = $this->getApplication();
            $request = $this->getRequest();
            $session = $this->getSession();
            $data = $request->getBodyParams();
            $optionValue = $this->getCurrentOptionValue();
            $customerId = $session->getCustomerId();
            $route = $data["route"];
            $cashOrVault = $data["cashOrVault"];
            $gmapsKey = $application->getGooglemapsKey();

            $staticMap = Request::staticMapFromRoute($route, $optionValue, $gmapsKey);

            $valueId = $optionValue->getId();
            $vehicleType = $data["vehicleType"];

            // Search for existing "pending" ride requests, prevent the user to request while waiting!
            $client = (new Client())->find($customerId, "customer_id");
            if (!$client->getId()) {
                throw new Exception(p__("cabride",
                    "Sorry, you are not registered as a Client, please contact the Application owner."));
            }

            if ($client->hasInProgressRequest()) {
                throw new Exception(p__("cabride",
                    "You already have a pending and/or a ride in progress, please wait before requesting another one!"));
            }

            $drivers = $vehicleType["drivers"];

            $vehicle = (new Vehicle())->find($vehicleType["id"]);
            $request = (new Request())->createRideRequest(
                $client->getId(), $vehicle, $valueId, $drivers, $cashOrVault, $route, $staticMap, "client");

            $sentToDrivers = false;
            foreach ($drivers as $index => $driver) {
                $driverId = $driver["driver_id"];
                $_tmpDriver = (new Driver())->find($driverId);
                if ($_tmpDriver->getId()) {
                    // Link & notify drivers
                    $requestDriver = new RequestDriver();
                    $requestDriver
                        ->setRequestId($request->getId())
                        ->setDriverId($driverId)
                        ->setStatus("pending")
                        ->save();

                    $_tmpDriver->notifyNewrequest($request->getId());
                    $sentToDrivers = true;
                }
            }

            if (!$sentToDrivers) {
                throw new Exception(p__("cabride",
                    "We are sorry, but an error occurred while sending your request to the available drivers!"));
            }

            $request->notify();
            
            $payload = [
                "success" => true,
                "message" => __("Success"),
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
     *
     */
    public function fetchAction ()
    {
        try {
            //$application = $this->getApplication();
            $request = $this->getRequest();
            $session = $this->getSession();
            //$optionValue = $this->getCurrentOptionValue();
            //$customerId = $session->getCustomerId();
            $requestId = $request->getParam("requestId", null);

            $request = (new Request())->findOneExtended($requestId);

            if (!$request["request_id"]) {
                throw new Exception(p__("cabride",
                    "This ride request doesn't exists!"));
            }

            $data = $request;

            // Makes payload lighter!
            unset($data["raw_route"]);

            $data["formatted_price"] = Base::_formatPrice($data["estimated_cost"]);
            $data["formatted_lowest_price"] = Base::_formatPrice($data["estimated_lowest_cost"]);

            $data["formatted_driver_price"] = false;
            if (!empty($data["driver_id"])) {
                $driver = (new Driver())->find($data["driver_id"]);
                $driverCustomer = (new Customer())->find($driver->getCustomerId());
                $distanceKm = ceil($request["distance"] / 1000);
                $durationMinute = ceil($request["duration"] / 60);
                $driverPrice = $driver->estimatePricing($distanceKm, $durationMinute, false);

                $data["formatted_driver_price"] = Base::_formatPrice($driverPrice);
                $data["driver"] = $driver->getData();
                $data["driverCustomer"] = $driverCustomer->getData();
            }

            $client = (new Client())->find($data["client_id"]);
            $clientCustomer = (new Customer())->find($client->getCustomerId());

            $data["client"] = $client->getData();
            $data["clientCustomer"] = $clientCustomer->getData();

            if ($data["payment_type"] === "credit-card") {
                $vault = (new ClientVault())->find($data["client_vault_id"]);

                $vaultData = $vault->getData();

                unset($vaultData["raw_payload"]);
                unset($vaultData["card_token"]);

                $data["vault"] = $vaultData;
                $data["cash"] = false;
            } else {
                $data["cash"] = true;
            }

            // Recast values
            $data["search_timeout"] = (integer) $data["search_timeout"];
            $data["timestamp"] = (integer) $data["timestamp"];

            // Fetch status history
            $logs = (new RequestLog())->findAll(["request_id = ?" => $requestId], ["created_at DESC"]);

            $data["logs"] = [];
            foreach($logs as $log) {
                $dataLog = $log->getData();

                $dateTime = \DateTime::createFromFormat(
                    "Y-m-d H:i:s",
                    $dataLog["created_at"]);

                $dataLog["timestamp"] = $dateTime->getTimestamp();

                $data["logs"][] = $dataLog;
            }

            $payload = [
                "success" => true,
                "request" => $data,
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
