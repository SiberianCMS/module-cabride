<?php

use Cabride\Model\Driver;
use Cabride\Model\Client;
use Cabride\Model\Request;
use Cabride\Model\RequestDriver;
use Cabride\Model\Vehicle;
use Siberian\Exception;
use Siberian_Google_Geocoding as Geocoding;
use Siberian\Feature;

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

            $collection = [];
            foreach ($drivers as $driver) {
                $vehicleId = $driver->getVehicleId();
                if (!array_key_exists($vehicleId, $collection)) {
                    $vehicle = (new Vehicle())->find($vehicleId);
                    $pricing = $vehicle->estimatePricing($distanceKm, $durationMinute);
                    $collection[$vehicleId] = [
                        "drivers" => [],
                        "id" => $vehicle->getId(),
                        "type" => $vehicle->getType(),
                        "pricing" => $pricing,
                    ];
                }

                $collection[$vehicleId]["drivers"][] = $driver->getFilteredData();
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

            $vehicle = (new Vehicle())->find($vehicleType["id"]);
            $request = (new Request())->createRideRequest(
                $client->getId(), $vehicle, $valueId, $route, $staticMap, "client");

            $sentToDrivers = false;
            foreach ($vehicleType["drivers"] as $index => $driver) {
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
}
