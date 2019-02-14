<?php

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
            $formula = Siberian_Google_Geocoding::getDistanceFormula($lat, $lng, "cabride_driver", "latitude", "longitude");

            $drivers = (new Cabride\Model\Driver())->findNearestOnline($valueId, $formula);

            $collection = [];
            foreach ($drivers as $driver) {
                $data = $driver->getData();
                $pricing = $driver->estimatePricing($distanceKm, $durationMinute);
                //$data[""]

                //$collection[] = ;
            }

            $payload = [
                "success" => true,
                "formula" => $formula,
                "collection" => $collection,
            ];

            dbg($payload);

        } catch (\Exception $e) {
            $payload = [
                "error" => true,
                "message" => __("An unknown error occurred, please try again later.")
            ];
        }

        $this->_sendJson($payload);
    }
}
