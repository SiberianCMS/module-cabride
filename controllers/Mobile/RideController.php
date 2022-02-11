<?php

use Cabride\Model\Client;
use Cabride\Model\Driver;
use Cabride\Model\Request;
use Cabride\Model\Vehicle;
use Cabride\Model\Payment;
use Cabride\Model\Cabride;
use Cabride\Model\RequestDriver;
use Cabride\Model\ClientVault;
use Cabride\Model\Stripe\Currency;
use Core\Model\Base;
use Siberian\Json;
use Siberian_Google_Geocoding as Geocoding;
use Cabride\Controller\Mobile as MobileController;

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

            $cabride = (new Cabride())->find($valueId, 'value_id');
            $client = (new Client())->find($customerId, 'customer_id');
            $rides = (new Request())->findExtended($valueId, $client->getId());

            $collection = [];
            foreach ($rides as $ride) {
                $data = $ride->getData();

                // Makes payload lighter!
                unset($data['raw_route']);

                $data['formatted_price'] = Base::_formatPrice($data['estimated_cost'], $cabride->getCurrency());
                $data['formatted_lowest_price'] = Base::_formatPrice($data['estimated_lowest_cost'], $cabride->getCurrency());

                $data['formatted_driver_price'] = false;
                if (!empty($data['driver_id'])) {
                    $driver = (new Driver())->find($data['driver_id']);
                    $distanceKm = ceil($ride->getDistance() / 1000);
                    $durationMinute = ceil($ride->getDuration() / 60);

                    if ($ride->getType() === 'course') {
                        $pricing = $driver->estimatePricing($distanceKm, $durationMinute, $ride->getSeats());
                    } else {
                        $pricing = $driver->estimatePricingTour($durationMinute, $ride->getSeats());
                    }

                    $driverPrice = $pricing['price'];

                    $data['formatted_driver_price'] = Base::_formatPrice($driverPrice, $cabride->getCurrency());

                    $driverCustomer = (new Customer_Model_Customer())->find($driver->getCustomerId());
                    $data['driver_phone'] = $driverCustomer->getMobile();

                    // Driver request!
                    $driverRequest = (new RequestDriver())->find([
                        'driver_id' => $driver->getId(),
                        'request_id' => $ride->getId(),
                    ]);

                    if ($driverRequest && $driverRequest->getId()) {
                        $data['eta_driver'] = (integer) $driverRequest->getEtaToClient();
                    }
                }

                // Recast values
                $now = time();
                $data['search_timeout'] = (integer) $data['search_timeout'];
                $data['timestamp'] = (integer) $data['timestamp'];
                $data['expires_in'] = (integer) ($data['expires_at'] - $now);

                $collection[] = $data;
            }

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

            $cabride = (new Cabride())->find($valueId, 'value_id');
            $client = (new Client())->find($customerId, 'customer_id');

            $payments = (new Payment())->fetchForClientId($client->getId());

            $cards = (new ClientVault())->findAll([
                'client_id = ?' => $client->getId(),
                'payment_provider = ?' => $cabride->getPaymentProvider(),
                'is_removed = ?' => 0,
            ]);

            $paymentData = [];
            foreach ($payments as $payment) {
                $data = $payment->getData();

                $data['formatted_amount'] = Base::_formatPrice($data['amount'], $data['currency']);

                $paymentData[] = $data;
            }

            $cardData = [];
            foreach ($cards as $card) {
                $data = $card->getData();

                unset($data['raw_payload']);

                $cardData[] = $data;
            }

            $payload = [
                'success' => true,
                'payments' => $paymentData,
                'cards' => $cardData,
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

            $cabride = (new Cabride())->find($valueId, 'value_id');
            $driver = (new Driver())->find($customerId, 'customer_id');
            $rides = (new Request())->findForDriver($valueId, $driver->getId(), 'aborted');

            $collection = [];
            foreach ($rides as $ride) {
                $data = $ride->getData();

                // Makes payload lighter!
                unset($data['raw_route']);

                $data['formatted_price'] = Base::_formatPrice($data['estimated_cost'], $cabride->getCurrency());

                // Recast values
                $now = time();
                $data['search_timeout'] = (integer) $data['search_timeout'];
                $data['timestamp'] = (integer) $data['timestamp'];
                $data['expires_in'] = (integer) ($data['expires_at'] - $now);

                $collection[] = $data;
            }

            $payload = [
                'success' => true,
                'collection' => $collection,
            ];
        } catch (\Exception $e) {
            $payload = [
                'error' => true,
                'message' => $e->getMessage()
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
            $requestId = $request->getParam('requestId', false);

            $cancelReason = $request->getBodyParams();

            $ride = (new Request())->find($requestId);

            if (!$requestId || !$ride->getId()) {
                throw new Exception(p__('cabride',
                    'Sorry, we are unable to find this ride request!'));
            }

            /**
             * @var $requestDrivers RequestDriver[]
             */
            $requestDrivers = (new RequestDriver())->findAll([
                'request_id' => $requestId,
            ]);

            foreach ($requestDrivers as $requestDriver) {
                $requestDriver->setStatus('aborted')->save();
            }

            $ride
                ->setCancelReason($cancelReason['reason'])
                ->setCancelNote($cancelReason['message'])
                ->save();

            $ride->changeStatus('aborted', Request::SOURCE_CLIENT);

            $payload = [
                'success' => true,
                'message' => p__('cabride', 'Your request is cancelled!'),
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
            $requestId = $request->getParam('requestId', false);

            $cancelReason = $request->getBodyParams();

            $ride = (new Request())->find($requestId);

            if (!$requestId || !$ride->getId()) {
                throw new Exception(p__('cabride',
                    'Sorry, we are unable to find this ride request!'));
            }

            /**
             * @var $requestDrivers RequestDriver[]
             */
            $requestDrivers = (new RequestDriver())->findAll([
                'request_id' => $requestId,
            ]);

            foreach ($requestDrivers as $requestDriver) {
                $requestDriver->setStatus('aborted')->save();
            }

            $ride
                ->setCancelReason($cancelReason['reason'])
                ->setCancelNote($cancelReason['message'])
                ->save();

            $ride->changeStatus('aborted', Request::SOURCE_DRIVER);

            $payload = [
                'success' => true,
                'message' => p__('cabride', 'Your request is cancelled!'),
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

            $cabride = (new Cabride())->find($valueId, 'value_id');
            $driver = (new Driver())->find($customerId, 'customer_id');
            $rides = (new Request())->findForDriver($valueId, $driver->getId(), 'pending');

            $collection = [];
            foreach ($rides as $ride) {
                $data = $ride->getData();

                // Makes payload lighter!
                unset($data['raw_route']);

                $data['formatted_price'] = Base::_formatPrice($data['estimated_cost'], $cabride->getCurrency());

                // Recast values
                $now = time();
                $data['search_timeout'] = (integer) $data['search_timeout'];
                $data['timestamp'] = (integer) $data['timestamp'];
                $data['expires_in'] = (integer) ($data['expires_at'] - $now);

                $collection[] = $data;
            }

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

            $cabride = (new Cabride())->find($valueId, 'value_id');
            $driver = (new Driver())->find($customerId, 'customer_id');
            $rides = (new Request())->findForDriver($valueId, $driver->getId(), ['accepted', 'onway', 'inprogress']);

            $collection = [];
            foreach ($rides as $ride) {
                $data = $ride->getData();

                // Makes payload lighter!
                unset($data['raw_route']);

                $data['formatted_price'] = Base::_formatPrice($data['cost'], $cabride->getCurrency());

                // Recast values
                $now = time();
                $data['search_timeout'] = (integer) $data['search_timeout'];
                $data['timestamp'] = (integer) $data['timestamp'];
                $data['expires_in'] = (integer) ($data['expires_at'] - $now);

                $client = (new Client())->find($ride->getClientId());
                $clientCustomer = (new Customer_Model_Customer())->find($client->getCustomerId());

                $data['client_phone'] = $clientCustomer->getMobile();

                $collection[] = $data;
            }

            $payload = [
                'success' => true,
                'collection' => $collection,
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

            $cabride = (new Cabride())->find($valueId, 'value_id');
            $driver = (new Driver())->find($customerId, 'customer_id');
            $rides = (new Request())->findForDriver($valueId, $driver->getId(), 'done');

            $collection = [];
            foreach ($rides as $ride) {
                $data = $ride->getData();

                // Makes payload lighter!
                unset($data['raw_route']);

                $data['formatted_price'] = Base::_formatPrice($data['cost'], $cabride->getCurrency());

                // Recast values
                $now = time();
                $data['search_timeout'] = (integer) $data['search_timeout'];
                $data['timestamp'] = (integer) $data['timestamp'];
                $data['expires_in'] = (integer) ($data['expires_at'] - $now);

                $collection[] = $data;
            }

            $payload = [
                'success' => true,
                'collection' => $collection,
            ];
        } catch (\Exception $e) {
            $payload = [
                'error' => true,
                'message' => $e->getMessage()
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     * Driver route
     */
    public function declinedAction ()
    {
        try {
            $request = $this->getRequest();
            $session = $this->getSession();
            $customerId = $session->getCustomerId();
            $optionValue = $this->getCurrentOptionValue();
            $valueId = $optionValue->getId();

            $cabride = (new Cabride())->find($valueId, 'value_id');
            $driver = (new Driver())->find($customerId, 'customer_id');
            $rides = (new Request())->findForDriver($valueId, $driver->getId(), 'declined');

            $collection = [];
            foreach ($rides as $ride) {
                $data = $ride->getData();

                // Makes payload lighter!
                unset($data['raw_route']);

                $data['formatted_price'] = Base::_formatPrice($data['estimated_cost'], $cabride->getCurrency());

                // Recast values
                $now = time();
                $data['search_timeout'] = (integer) $data['search_timeout'];
                $data['timestamp'] = (integer) $data['timestamp'];
                $data['expires_in'] = (integer) ($data['expires_at'] - $now);

                $collection[] = $data;
            }

            $payload = [
                'success' => true,
                'collection' => $collection,
            ];
        } catch (\Exception $e) {
            $payload = [
                'error' => true,
                'message' => $e->getMessage()
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
            $requestId = $request->getParam('requestId', false);

            $cabride = (new Cabride())->find($valueId, 'value_id');
            $ride = (new Request())->find($requestId);

            if (!$requestId || !$ride->getId()) {
                throw new Exception(p__('cabride',
                    'Sorry, we are unable to find this ride request!'));
            }

            $driver = (new Driver())->find($customerId, 'customer_id');

            $requestDriver = (new RequestDriver())->find([
                'request_id' => $requestId,
                'driver_id' => $driver->getId(),
                'status' => 'pending'
            ]);

            if ($requestDriver->getId()) {
                $requestDriver
                    ->setStatus('declined')
                    ->save();
            }

            $payload = [
                'success' => true,
                'message' => p__('cabride', 'You declined the request!'),
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
            $requestId = $request->getParam('requestId', false);
            $data = $request->getBodyParams();
            $route = $data['route'];

            $cabride = (new Cabride())->find($valueId, 'value_id');
            $ride = (new Request())->find($requestId);

            if (!$requestId || !$ride->getId()) {
                throw new Exception(p__('cabride',
                    'Sorry, we are unable to find this ride request!'));
            }

            $driver = (new Driver())->find($customerId, 'customer_id');

            $requestDriver = (new RequestDriver())->find([
                'request_id' => $requestId,
                'driver_id' => $driver->getId(),
                'status' => ['pending', 'declined']
            ]);

            if (!$requestDriver->getId()) {
                throw new Exception(p__('cabride',
                    'Sorry, we are unable to find this ride request!'));
            }

            $requestDriver
                ->setRawRoute(Json::encode($route))
                ->setStatus('accepted')
                ->save();

            $distanceKm = ceil($ride->getDistance() / 1000);
            $durationMinute = ceil($ride->getDuration() / 60);

            if ($ride->getType() === 'course') {
                $pricing = $driver->estimatePricing($distanceKm, $durationMinute, $ride->getSeats());
            } else {
                $pricing = $driver->estimatePricingTour($durationMinute, $ride->getSeats());
            }

            $driverPrice = $pricing['price'];

            $ride->setCost($driverPrice);

            $ride->changeStatus('accepted', Request::SOURCE_DRIVER);

            $ride
                ->setDriverId($driver->getId())
                ->save();

            // So also expires all other drivers!
            $requestDrivers = (new RequestDriver())
                ->findAll(['request_id = ?' => $requestId, 'driver_id != ?' => $driver->getId()]);
            foreach ($requestDrivers as $requestDriver) {
                $requestDriver
                    ->setStatus('accepted_other')
                    ->save();
            }

            $payload = [
                'success' => true,
                'message' => p__('cabride', 'You finally accepted the request!'),
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
     * Driver route
     */
    public function vehicleInformationAction ()
    {
        try {
            $session = $this->getSession();
            $customer = $session->getCustomer();
            $customerId = $session->getCustomerId();
            $optionValue = $this->getCurrentOptionValue();
            $valueId = $optionValue->getId();

            $cabride = (new Cabride())->find($valueId, 'value_id');
            $driver = (new Driver())->find($customerId, 'customer_id');

            if (!$driver || !$driver->getId()) {
                throw new Exception(p__('cabride',
                    'Sorry, we are unable to find your driver profile!'));
            }

            // So also expires all other drivers!
            $vehicleTypes = (new Vehicle())
                ->findAll([
                    'value_id = ?' => $valueId,
                    'is_visible = ?' => 1
                ]);

            $driverData = $driver->toJson();

            $fareKeyValue = [
                'baseFare' => 'base_fare',
                'distanceFare' => 'distance_fare',
                'timeFare' => 'time_fare',
                'extraSeatFare' => 'extra_seat_fare',
                'extraSeatDistanceFare' => 'seat_distance_fare',
                'extraSeatTimeFare' => 'seat_time_fare',
                'tourBaseFare' => 'tour_base_fare',
                'tourTimeFare' => 'tour_time_fare',
                'tourExtraSeatBaseFare' => 'extra_seat_tour_base_fare',
                'tourExtraSeatTimeFare' => 'extra_seat_tour_time_fare',
            ];

            $types = [];
            $currentType = null;
            foreach ($vehicleTypes as $vehicleType) {
                $data = $vehicleType->getData();

                $data['id'] = $data['vehicle_id'];
                $data['label'] = $data['type'];

                foreach ($fareKeyValue as $key => $value) {
                    $data[$key] = ($data[$value] > 0) ?
                        Base::_formatPrice($data[$value], $cabride->getCurrency()) : 0;
                    $data[$key] = (float) $data[$key];
                }

                $types[] = $data;

                if ($driverData['hasVehicle'] && $data['id'] == $driverData['vehicle_id']) {
                    $currentType = $data;
                }
            }

            $driverCustomer = (new Customer_Model_Customer())->find($driver->getCustomerId());

            $driverData['driver_phone'] = $driverCustomer->getMobile();

            $payload = [
                'success' => true,
                'driver' => $driverData,
                'vehicleTypes' => $types,
                'currentType' => $currentType,
                'message' => p__('cabride', 'You finally accepted the request!'),
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
     * Driver route
     */
    public function selectVehicleTypeAction ()
    {
        try {
            $request = $this->getRequest();
            $session = $this->getSession();
            $customer = $session->getCustomer();
            $customerId = $session->getCustomerId();
            $typeId = $request->getParam('typeId', false);
            $optionValue = $this->getCurrentOptionValue();
            $valueId = $optionValue->getId();

            $cabride = (new Cabride())->find($valueId, 'value_id');
            $driver = (new Driver())->find($customerId, 'customer_id');

            if (!$driver || !$driver->getId()) {
                throw new Exception(p__('cabride',
                    'Sorry, we are unable to find your driver profile!'));
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

            $fareKeyValue = [
                'baseFare' => 'base_fare',
                'distanceFare' => 'distance_fare',
                'timeFare' => 'time_fare',
                'extraSeatFare' => 'extra_seat_fare',
                'extraSeatDistanceFare' => 'seat_distance_fare',
                'extraSeatTimeFare' => 'seat_time_fare',
                'tourBaseFare' => 'tour_base_fare',
                'tourTimeFare' => 'tour_time_fare',
                'tourExtraSeatBaseFare' => 'extra_seat_tour_base_fare',
                'tourExtraSeatTimeFare' => 'extra_seat_tour_time_fare',
            ];

            $currentType['id'] = $currentType['vehicle_id'];
            $currentType['label'] = $currentType['type'];

            foreach ($fareKeyValue as $key => $value) {
                $data[$key] = ($currentType[$value] > 0) ?
                    Base::_formatPrice($currentType[$value], $cabride->getCurrency()) : 0;
                $data[$key] = (float) $data[$key];
            }

            $driverData = $driver->toJson();
            $driverData['driver_phone'] = $customer->getMobile();

            $payload = [
                'success' => true,
                'driver' => $driverData,
                'currentType' => $currentType,
                'message' => p__('cabride', 'Success!'),
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
     * Driver route
     */
    public function saveDriverAction ()
    {
        try {
            $request = $this->getRequest();
            $application = $this->getApplication();
            $session = $this->getSession();
            $customer = $session->getCustomer();
            $data = $request->getBodyParams();
            $driverParams = $data['driver'];
            $valueId = Cabride::getCurrentValueId();

            $cabride = (new Cabride())->find($valueId, 'value_id');
            $driver = (new Driver())->find($driverParams['driver_id']);

            if (!$driver->getId()) {
                throw new Exception(p__('cabride',
                    'Sorry, we are unable to find your driver profile!'));
            }

            $errors = [];
            if (empty($driverParams['driver_license'])) {
                $errors[] = p__('cabride', 'Driver license');
            }

            if (empty($driverParams['vehicle_license_plate'])) {
                $errors[] = p__('cabride', 'License plate');
            }

            if (empty($driverParams['driver_phone'])) {
                $errors[] = p__('cabride', 'Mobile number');
            }

            // Geocoding base address
            $position = Geocoding::getLatLng(
                ['address' => $driverParams['base_address']],
                $application->getGooglemapsKey());

            if (empty($position[0]) || empty($position[1])) {
                $errors[] = p__('cabride', 'Invalid address!');
            }

            if ($cabride->getPricingMode() === 'driver') {
                if ($driverParams['base_fare'] <= 0 &&
                    ($driverParams['distance_fare'] <= 0 || $driverParams['time_fare'] <= 0)) {
                    $errors[] = p__('cabride', 'Driving fares!');
                }

                if (empty($driverParams['base_fare']) &&
                    (empty($driverParams['distance_fare']) || empty($driverParams['time_fare']))) {
                    $errors[] = p__('cabride', 'Driving fares!');
                }

                $keys = [
                    'base_fare',
                    'distance_fare',
                    'time_fare',
                    'extra_seat_fare',
                    'seat_distance_fare',
                    'seat_time_fare',
                    'tour_base_fare',
                    'tour_time_fare',
                    'extra_seat_tour_base_fare',
                    'extra_seat_tour_time_fare',
                ];

                foreach ($keys as $key) {
                    $driver->setData($key, $driverParams[$key]);
                }
            }

            $driver
                ->setSeats($driverParams['seats'])
                ->setVehicleId($driverParams['vehicle_id'])
                ->setVehicleModel($driverParams['vehicle_model'])
                ->setVehicleLicensePlate($driverParams['vehicle_license_plate'])
                ->setDriverLicense($driverParams['driver_license'])
                ->setDriverPhone($driverParams['driver_phone'])
                ->setBaseAddress($driverParams['base_address'])
                ->setBaseLatitude($position[0])
                ->setBaseLongitude($position[1])
                ->setPickupRadius($driverParams['pickup_radius']);

            if (count($errors) > 0) {
                foreach ($errors as &$error) {
                    $error = '- {$error}';
                }
                throw new Exception(implode('<br />', $errors));
            }

            $driver->save();

            // Update account mobile number!
            $customer
                ->setMobile($driverParams['driver_phone'])
                ->save();

            $driverData = $driver->toJson();

            $payload = [
                'success' => true,
                'driver' => $driverData,
                'message' => p__('cabride', 'You vehicle information are saved!'),
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
            $requestId = $request->getParam('requestId', false);

            $data = $request->getBodyParams();
            $route = $data['route'];

            $cabride = (new Cabride())->find($valueId, 'value_id');
            $ride = (new Request())->find($requestId);

            if (!$requestId || !$ride->getId()) {
                throw new Exception(p__('cabride',
                    'Sorry, we are unable to find this ride request!'));
            }

            $driver = (new Driver())->find($customerId, 'customer_id');

            $requestDriver = (new RequestDriver())->find([
                'request_id' => $requestId,
                'driver_id' => $driver->getId(),
                'status' => ['accepted', 'onway']
            ]);

            if (!$requestDriver->getId()) {
                throw new Exception(p__('cabride',
                    'Sorry, we are unable to find this ride request!'));
            }

            if ($ride->getType() === 'course') {
                $timeToClient = (integer) $route['routes'][0]['legs'][0]['duration']['value'];
                $timeToDestination =
                    (integer) $route['routes'][0]['legs'][0]['duration']['value'] +
                    (integer) $route['routes'][0]['legs'][1]['duration']['value'];
            } else if ($ride->getType() === 'tour') {
                $timeToClient = 0;
                $timeToDestination = 0;
            }


            $requestDriver
                ->setStatus('onway')
                ->setEtaToClient($timeToClient + time())
                ->setEtaToDestination($timeToDestination + time())
                ->setTimeToClient($timeToClient)
                ->setTimeToDestination($timeToDestination)
                ->save();

            $ride->changeStatus('onway', Request::SOURCE_DRIVER);

            $payload = [
                'success' => true,
                'driveTo' => [
                    'lat' => (float) $ride->getFromLat(),
                    'lng' => (float) $ride->getFromLng(),
                ],
                'message' => p__('cabride', 'We notified your passenger that you are on his way!'),
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
            $requestId = $request->getParam('requestId', false);

            $cabride = (new Cabride())->find($valueId, 'value_id');
            $ride = (new Request())->find($requestId);

            if (!$requestId || !$ride->getId()) {
                throw new Exception(p__('cabride',
                    'Sorry, we are unable to find this ride request!'));
            }

            $driver = (new Driver())->find($customerId, 'customer_id');

            $requestDriver = (new RequestDriver())->find([
                'request_id' => $requestId,
                'driver_id' => $driver->getId(),
                'status' => ['accepted', 'onway']
            ]);

            if (!$requestDriver->getId()) {
                throw new Exception(p__('cabride',
                    'Sorry, we are unable to find this ride request!'));
            }

            $requestDriver
                ->setStatus('inprogress')
                ->save();

            $ride->changeStatus('inprogress', Request::SOURCE_DRIVER);

            $payload = [
                'success' => true,
                'driveTo' => [
                    'lat' => (float) $ride->getToLat(),
                    'lng' => (float) $ride->getToLng(),
                ],
                'message' => p__('cabride', 'Opening navigation!'),
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
            $requestId = $request->getParam('requestId', false);
            $route = $request->getParam('route', false);

            $cabride = (new Cabride())->find($valueId, 'value_id');
            $ride = (new Request())->find($requestId);

            if (!$requestId || !$ride->getId()) {
                throw new Exception(p__('cabride',
                    'Sorry, we are unable to find this ride request!'));
            }

            $clientId = $ride->getClientId();
            $client = (new Client())->find($clientId);
            $cabride = (new Cabride())->find($valueId, 'value_id');
            $driver = (new Driver())->find($customerId, 'customer_id');

            $status = 'inprogress';
            if ($ride->getType() === 'tour') {
                $status = 'onway';
            }
            $requestDriver = (new RequestDriver())->find([
                'request_id' => $requestId,
                'driver_id' => $driver->getId(),
                'status' => $status
            ]);

            if (!$requestDriver->getId()) {
                throw new Exception(p__('cabride',
                    'Sorry, we are unable to find this ride request!'));
            }

            $driver = (new Driver())->find($ride->getDriverId());

            $distanceKm = ceil($ride->getDistance() / 1000);
            $durationMinute = ceil($ride->getDuration() / 60);

            if ($ride->getType() === 'course') {
                $pricing = $driver->estimatePricing($distanceKm, $durationMinute, $ride->getSeats());
            } else {
                $pricing = $driver->estimatePricingTour($durationMinute, $ride->getSeats());
            }

            $driverPrice = $pricing['price'];

            $requestDriver
                ->setStatus('done')
                ->save();

            $ride->setCost($driverPrice);
            $ride->changeStatus('done', Request::SOURCE_DRIVER);

            $charge = null;
            $status = 'paid';

            $stripeCost = round($ride->getCost());

            // zero-decimals stripe currencies ....
            if (!in_array($cabride->getCurrency(), Currency::$zeroDecimals)) {
                $stripeCost = round($ride->getCost() * 100);
            }

            // Create the payment
            $payment = new Payment();

            // Fetching the paymentId
            $paymentId = $ride->getPaymentId();

            $paymentMethod = \PaymentMethod\Model\Payment::createOrGetFromModal($paymentId);
            $paymentIntent = $paymentMethod->retrieve();
            $ccMethod = $paymentIntent->getPaymentMethod();
            $paymentGateway = $paymentMethod->gateway();

            $paymentGateway->capture($paymentIntent, [
                'amount_to_capture' => $stripeCost
            ]);

            if ($ccMethod) {
                $payment
                    ->setBrand($ccMethod->getBrand())
                    ->setLast($ccMethod->getLast())
                    ->setExp($ccMethod->getExp());
            }

            $payment
                ->setValueId($valueId)
                ->setRequestId($ride->getId())
                ->setDriverId($driver->getId())
                ->setClientId($client->getId())
                ->setAmountCharged($stripeCost)
                ->setAmount($ride->getCost())
                ->setCurrency($cabride->getCurrency())
                ->setMethod($paymentGateway::$paymentMethod)
                ->setProvider($paymentGateway::$shortName)
                ->setPaymentMethodId(2)
                ->setPaymentApiVersion(2)
                ->setStatus($status)
                ->save();


            $payment->addCommission();

            $payload = [
                'success' => true,
                'message' => p__('cabride', 'The course is now marked are complete!'),
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
     * Driver route
     */
    public function notifyClientAction ()
    {
        try {
            $request = $this->getRequest();
            $requestId = $request->getParam('requestId', false);

            $ride = (new Request())->find($requestId);

            if (!$requestId || !$ride || !$ride->getId()) {
                throw new Exception(p__('cabride',
                    'Sorry, we are unable to find this ride request!'));
            }

            $ride->notifyCustomer();

            $payload = [
                'success' => true,
                'message' => p__('cabride', 'The client is notified!'),
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
     * Rate the ride
     */
    public function rateCourseAction ()
    {
        try {
            $request = $this->getRequest();
            $requestId = $request->getParam('requestId', false);
            $data = $request->getBodyParams();
            $ride = (new Request())->find($requestId);

            if (!$requestId || !$ride->getId()) {
                throw new Exception(p__('cabride',
                    'Sorry, we are unable to find this ride request!'));
            }

            $ride
                ->setCourseRating($data['rating']['course'])
                ->setCourseComment($data['rating']['comment'])
                ->save();

            $payload = [
                'success' => true,
                'message' => p__('cabride', 'Thanks!'),
            ];
        } catch (\Exception $e) {
            $payload = [
                'error' => true,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ];
        }

        $this->_sendJson($payload);
    }
}
