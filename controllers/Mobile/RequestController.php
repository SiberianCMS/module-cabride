<?php

use Cabride\Model\Cabride;
use Cabride\Model\Driver;
use Cabride\Model\Client;
use Cabride\Model\ClientVault;
use Cabride\Model\Request;
use Cabride\Model\RequestLog;
use Cabride\Model\Vehicle;
use Core\Model\Base;
use Customer_Model_Customer as Customer;
use Siberian\Exception;
use Siberian\Json;
use Siberian_Google_Geocoding as Geocoding;
use Cabride\Controller\Mobile as MobileController;

/**
 * Class Cabride_Mobile_RequestController
 */
class Cabride_Mobile_RequestController extends MobileController
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

            $cabrideOption = Cabride::getCurrent();
            $cabride = (new Cabride())->find($cabrideOption->getValueId(), 'value_id');

            $valueId = $optionValue->getId();
            $route = $data['route'];
            $ride = $data['ride'];
            $seats = $ride['seats'] ?? 1;
            $request = $route['request'];
            $origin = $request['origin']['location'];
            $lat = $origin['lat'];
            $lng = $origin['lng'];

            $distanceKm = ceil($route['routes'][0]['legs'][0]['distance']['value'] / 1000);
            $durationMinute = ceil($route['routes'][0]['legs'][0]['duration']['value'] / 60);

            // Searching for closest drivers!
            // Attention, distance is computed on the fly!
            $formula = Geocoding::getDistanceFormula($lat, $lng, 'd', 'latitude', 'longitude');

            // Number of seats
            $params = [];
            if ($cabride->getEnableSeats()) {
                if ($cabride->getPricingMode() === 'fixed') {
                    $params['v.seats >= ?'] = $seats;
                } else {
                    $params['d.seats >= ?'] = $seats;
                }
            }

            $drivers = (new Driver())
                ->findNearestOnline($valueId, $formula, $params);

            $client = (new Client())->find($customerId, 'customer_id');

            $collection = [];
            foreach ($drivers as $driver) {

                $_tmpDriver = (new Driver())->find($driver->getId());

                $vehicleId = $_tmpDriver->getVehicleId();

                $pricingCourse = $driver->estimatePricing($distanceKm, $durationMinute, $seats);

                $pricing = $pricingCourse['format'];
                $pricingValue = $pricingCourse['price'];

                if (!array_key_exists($vehicleId, $collection)) {
                    $vehicle = (new Vehicle())->find($vehicleId);
                    $collection[$vehicleId] = [
                        'drivers' => [],
                        'id' => (int) $vehicle->getId(),
                        'type' => $vehicle->getType(),
                        'icon' => $vehicle->getIcon(),
                        'pricing' => $pricing,
                        'pricingValue' => $pricingValue,
                        'lowPricing' => $pricing,
                        'lowPricingValue' => $pricingValue,
                        'prices' => [],
                    ];
                } else {
                    if ($pricingValue > $collection[$vehicleId]['pricingValue']) {
                        // Gives highest estimate to passenger!
                        $collection[$vehicleId]['pricing'] = $pricing;
                        $collection[$vehicleId]['pricingValue'] = $pricingValue;
                    }

                    if ($pricingValue < $collection[$vehicleId]['lowPricingValue']) {
                        // Lowest estimate to passenger!
                        $collection[$vehicleId]['lowPricing'] = $pricing;
                        $collection[$vehicleId]['lowPricingValue'] = $pricingValue;
                    }
                }

                $collection[$vehicleId]['drivers'][] = $_tmpDriver->getFilteredData();
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
                'success' => true,
                'collection' => $collection,
                'vaults' => $vaults,
            ];
        } catch (\Exception $e) {
            $payload = [
                'error' => true,
                'message' => __('An unknown error occurred, please try again later.'),
                'except' => $e->getMessage()
            ];
        }

        $this->_sendJson($payload);
    }

    public function tourAction()
    {
        try {
            $request = $this->getRequest();
            $data = $request->getBodyParams();
            $optionValue = $this->getCurrentOptionValue();
            $session = $this->getSession();
            $customerId = $session->getCustomerId();

            $cabrideOption = Cabride::getCurrent();
            $cabride = (new Cabride())->find($cabrideOption->getValueId(), 'value_id');

            $valueId = $optionValue->getId();
            $ride = $data['ride'];
            $seats = $ride['seats'] ?? 1;
            $duration = $ride['duration'];
            $origin = $ride['pickup'];
            $lat = $origin['latitude'];
            $lng = $origin['longitude'];

            // Searching for closest drivers!
            // Attention, distance is computed on the fly!
            $formula = Geocoding::getDistanceFormula($lat, $lng, 'd', 'latitude', 'longitude');

            // Number of seats
            $params = [];
            if ($cabride->getEnableSeats()) {
                if ($cabride->getPricingMode() === 'fixed') {
                    $params['v.seats >= ?'] = $seats;
                } else {
                    $params['d.seats >= ?'] = $seats;
                }
            }

            $drivers = (new Driver())
                ->findNearestOnline($valueId, $formula, $params);

            $client = (new Client())->find($customerId, 'customer_id');
            $cabride = (new Cabride())->find($optionValue->getId(), 'value_id');

            $collection = [];
            foreach ($drivers as $driver) {

                $_tmpDriver = (new Driver())->find($driver->getId());

                $vehicleId = $_tmpDriver->getVehicleId();
                $pricing = $_tmpDriver->estimatePricingTour($duration, $seats);

                if (!array_key_exists($vehicleId, $collection)) {
                    $vehicle = (new Vehicle())->find($vehicleId);
                    $collection[$vehicleId] = [
                        'drivers' => [],
                        'id' => (int) $vehicle->getId(),
                        'type' => $vehicle->getType(),
                        'icon' => $vehicle->getIcon(),
                        'pricing' => $pricing['format'],
                        'pricingValue' => $pricing['price'],
                        'lowPricing' => $pricing['format'],
                        'lowPricingValue' => $pricing['price'],
                        'prices' => [],
                    ];
                } else {
                    if ($pricing['price'] > $collection[$vehicleId]['pricingValue']) {
                        // Gives highest estimate to passenger!
                        $collection[$vehicleId]['pricing'] = $pricing['format'];
                        $collection[$vehicleId]['pricingValue'] = $pricing['price'];
                    }

                    if ($pricing['price'] < $collection[$vehicleId]['lowPricingValue']) {
                        // Lowest estimate to passenger!
                        $collection[$vehicleId]['lowPricing'] = $pricing['format'];
                        $collection[$vehicleId]['lowPricingValue'] = $pricing['price'];
                    }
                }

                $collection[$vehicleId]['drivers'][] = $_tmpDriver->getFilteredData();
            }

            $type = $cabride->getPaymentProvider();

            $payload = [
                'success' => true,
                'collection' => $collection,
            ];
        } catch (\Exception $e) {
            $payload = [
                'error' => true,
                'message' => __('An unknown error occurred, please try again later.'),
                'except' => $e->getMessage()
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     *
     */
    public function validateAction()
    {
        try {
            $application = $this->getApplication();
            $request = $this->getRequest();
            $session = $this->getSession();
            $data = $request->getBodyParams();
            $optionValue = $this->getCurrentOptionValue();
            $customerId = $session->getCustomerId();
            $route = $data['route'];
            $ride = $data['ride'] ?? null;
            $cashOrVault = $data['cashOrVault'];
            $gmapsKey = $application->getGooglemapsKey();
            $customFormFields = $data['customFormFields'];

            $valueId = $optionValue->getId();
            $vehicleType = $data['vehicleType'];

            // Search for existing 'pending' ride requests, prevent the user to request while waiting!
            $client = (new Client())->find($customerId, 'customer_id');
            if (!$client || !$client->getId()) {
                throw new Exception(p__('cabride',
                    'Sorry, you are not registered as a Client, please contact the Application owner.'));
            }

            if ($client->hasInProgressRequest()) {
                throw new Exception(p__('cabride',
                    'You already have a pending and/or a ride in progress, please wait before requesting another one!'));
            }

            $drivers = $vehicleType['drivers'];

            $type = 'course';
            $seats = 1;
            if ($ride !== null) {
                $type = $ride['type'] ?? 'course';
                $seats = $ride['seats'] ?? 'course';
            }

            if ($type === 'course') {
                $staticMap = Request::staticMapFromRoute($route, $optionValue, $gmapsKey);
            } else if ($type === 'tour') {
                $pickup = [
                    'lat' => $ride['pickup']['latitude'],
                    'lng' => $ride['pickup']['longitude'],
                ];
                $staticMap = Request::staticMapFromPickup($pickup, $optionValue, $gmapsKey);
            }

            $vehicle = (new Vehicle())->find($vehicleType["id"]);
            (new Request())->createRideRequest(
                $client->getId(),
                $vehicle,
                $valueId,
                $drivers,
                $cashOrVault,
                $route,
                $ride,
                $staticMap,
                $customFormFields,
                Request::SOURCE_CLIENT,
                $type,
                $seats);

            $payload = [
                'success' => true,
                'message' => __('Success'),
            ];
        } catch (\Exception $e) {
            $payload = [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     *
     */
    public function validateV2Action()
    {
        try {
            $application = $this->getApplication();
            $request = $this->getRequest();
            $session = $this->getSession();
            $data = $request->getBodyParams();
            $optionValue = $this->getCurrentOptionValue();
            $customerId = $session->getCustomerId();
            $route = $data['route'];
            $ride = $data['ride'] ?? null;
            $paymentId = $data['paymentId'];
            $hasCustomOffer = $data['hasCustomOffer'] ?? false;
            $customOfferAmount = $data['customOfferAmount'] ?? false;
            $gmapsKey = $application->getGooglemapsKey();
            $customFormFields = $data['customFormFields'];

            $valueId = $optionValue->getId();
            $vehicleType = $data['vehicleType'];

            // Search for existing 'pending' ride requests, prevent the user to request while waiting!
            $client = (new Client())->find($customerId, 'customer_id');
            if (!$client || !$client->getId()) {
                throw new Exception(p__('cabride',
                    'Sorry, you are not registered as a Client, please contact the Application owner.'));
            }

            if ($client->hasInProgressRequest()) {
                throw new Exception(p__('cabride',
                    'You already have a pending and/or a ride in progress, please wait before requesting another one!'));
            }

            $drivers = $vehicleType['drivers'];

            $type = 'course';
            $seats = 1;
            if ($ride !== null) {
                $type = $ride['type'] ?? 'course';
                $seats = $ride['seats'] ?? 'course';
            }

            if ($type === 'course') {
                $staticMap = Request::staticMapFromRoute($route, $optionValue, $gmapsKey);
            } else if ($type === 'tour') {
                $pickup = [
                    'lat' => $ride['pickup']['latitude'],
                    'lng' => $ride['pickup']['longitude'],
                ];
                $staticMap = Request::staticMapFromPickup($pickup, $optionValue, $gmapsKey);
            }

            $vehicle = (new Vehicle())->find($vehicleType['id']);
            (new Request())->createRideRequestV2(
                $client->getId(),
                $vehicle,
                $valueId,
                $drivers,
                $paymentId,
                $route,
                $ride,
                $staticMap,
                $customFormFields,
                Request::SOURCE_CLIENT,
                $type,
                $seats,
                $hasCustomOffer,
                $customOfferAmount);

            $payload = [
                'success' => true,
                'message' => __('Success'),
            ];
        } catch (\Exception $e) {
            $payload = [
                'error' => true,
                'message' => $e->getMessage(),
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
            $request = $this->getRequest();
            $session = $this->getSession();
            $optionValue = $this->getCurrentOptionValue();
            $valueId = $optionValue->getId();
            $requestId = $request->getParam('requestId', null);

            $cabride = (new Cabride())->find($valueId, 'value_id');
            $request = (new Request())->findOneExtended($requestId);

            if (!$request['request_id']) {
                throw new Exception(p__('cabride',
                    "This ride request doesn't exists!"));
            }

            $data = $request;

            // Makes payload lighter!
            unset($data['raw_route']);

            $data['formatted_price'] = Base::_formatPrice($data['estimated_cost'], $cabride->getCurrency());
            $data['formatted_cost'] = Base::_formatPrice($data['cost'], $cabride->getCurrency());
            $data['formatted_lowest_price'] = Base::_formatPrice($data['estimated_lowest_cost'], $cabride->getCurrency());

            $data['formatted_driver_price'] = false;
            if (!empty($data['driver_id'])) {
                $driver = (new Driver())->find($data['driver_id']);
                $driverCustomer = (new Customer())->find($driver->getCustomerId());
                $distanceKm = ceil($request['distance'] / 1000);
                $durationMinute = ceil($request['duration'] / 60);

                if ($request['type'] === 'course') {
                    $pricing = $driver->estimatePricing($distanceKm, $durationMinute, $request['seats']);
                } else {
                    $pricing = $driver->estimatePricingTour($durationMinute, $request['seats']);
                }

                $data['formatted_driver_price'] = $pricing['format'];
                $data['driver'] = $driver->getData();
                $data['driverCustomer'] = $driverCustomer->getData();
            }

            $client = (new Client())->find($data['client_id']);
            $clientCustomer = (new Customer())->find($client->getCustomerId());

            $data['client'] = $client->getData();
            $data['clientCustomer'] = $clientCustomer->getData();

            if ($data['payment_type'] === 'credit-card') {
                $paymentId = $request['payment_id'];
                $paymentMethod = PaymentMethod\Model\Payment::createOrGetFromModal($paymentId);
                $paymentIntent = $paymentMethod->retrieve();
                $creditCard = $paymentIntent->getPaymentMethod();

                $data['vault'] = [
                    'last' => $creditCard->getData('last'),
                    'brand' => $creditCard->getData('brand'),
                    'exp' => $creditCard->getData('exp'),
                ];
                $data['cash'] = false;
            } else {
                $data['cash'] = true;
            }

            // Recast values
            $now = time();
            $data['type'] = $request['type'];
            $data['search_timeout'] = (integer) $data['search_timeout'];
            $data['timestamp'] = (integer) $data['timestamp'];
            $data['expires_in'] = (integer) ($data['expires_at'] - $now);
            $data['customFormFields'] = Json::decode(base64_decode($request['custom_form_fields']));

            // Fetch status history
            $logs = (new RequestLog())->findAll(['request_id = ?' => $requestId], ['created_at DESC']);

            $data['logs'] = [];
            foreach($logs as $log) {
                $dataLog = $log->getData();
                $data['logs'][] = $dataLog;
            }

            $payload = [
                'success' => true,
                'request' => $data,
            ];
        } catch (\Exception $e) {
            $payload = [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }

        $this->_sendJson($payload);
    }

}
