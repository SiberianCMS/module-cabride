<?php

use Cabride\Controller\Base;
use Siberian\Exception;
use Cabride\Model\Driver;
use Cabride\Model\Client;
use Cabride\Model\Request;
use Cabride\Model\RequestDriver;
use Cabride\Model\Cashreturn;

/**
 * Class Cabride_Api_MessageController
 */
class Cabride_Api_MessageController extends Base
{

    /**
     * @var array
     */
    public $secured_actions = [
        'settings',
        'join-lobby',
        'send-request',
        'aggregate-information',
    ];

    /**
     * Fetch settings & ssl certificates to run wss://
     */
    public function settingsAction()
    {
        try {
            /**
             * @var $sslCertificate System_Model_SslCertificates
             */
            $sslCertificate = (new System_Model_SslCertificates())
                ->find([
                    'hostname' => $this->getRequest()->getHttpHost()
                ]);
            if (!$sslCertificate->getId()) {
                throw new Exception(__('Unable to find a corresponding SSL Certificate!'));
            }

            $payload = [
                'success' => true,
                'privateKey' => file_get_contents($sslCertificate->getPrivate()),
                'chain' => file_get_contents($sslCertificate->getChain()),
                'certificate' => file_get_contents($sslCertificate->getCertificate())
            ];

        } catch (\Exception $e) {
            $payload = [
                'error' => true,
                'message' => $e->getMessage()
            ];
        }

        $this->_sendJson($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_LINE_TERMINATORS);
    }

    /**
     * User must join the lobby before any action!
     */
    public function joinLobbyAction()
    {
        try {
            $this->belongsToApp();

            $payload = [
                'success' => true,
                'user' => $this->user,
                'userId' => $this->userId,
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
    public function updatePositionAction()
    {
        try {
            $this->belongsToApp();

            $request = $this->getRequest();
            $data = $request->getBodyParams();

            $driver = (new Driver)->find($this->userId, 'customer_id');
            if ($driver && $driver->getId()) {
                $driver
                    ->setLatitude($data['position']['latitude'])
                    ->setLongitude($data['position']['longitude'])
                    ->save();
            }

            $payload = [
                'success' => true,
                'user' => $this->user,
                'userId' => $this->userId,
            ];
        } catch (\Exception $e) {
            $payload = [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }

        $this->_sendJson($payload);
    }

    public function aggregateInformationAction()
    {
        try {
            $this->belongsToApp();

            $isDriver = false;
            $isPassenger = false;
            $mustFillVehicle = false;

            $data = [
                'counters' => [
                    'pending' => 0,
                    'accepted' => 0,
                    'declined' => 0,
                    'done' => 0,
                    'rides' => 0,
                    'paymentHistory' => 0,
                ]
            ];

            $driver = (new Driver)->find($this->userId, 'customer_id');
            if ($driver && $driver->getId()) {
                $isDriver = true;

                // Find ride requests
                $statuses = [
                    'pending',
                    'accepted',
                    'onway',
                    'inprogress',
                    'declined',
                    'done',
                ];
                $rideRequests = (new RequestDriver())
                    ->fetchForDriver($driver->getId(), $statuses);

                foreach ($rideRequests as $rideRequest) {
                    $status = $rideRequest->getStatus();
                    switch ($status) {
                        case 'pending':
                            $data['counters']['pending']++;
                            break;
                        case 'accepted':
                        case 'onway':
                        case 'inprogress':
                            $data['counters']['accepted']++;
                            break;
                        case 'declined':
                            $data['counters']['declined']++;
                            break;
                        case 'done':
                            $data['counters']['done']++;
                            break;
                    }
                }

                $mustFillVehicle = count($driver->getProfileErrors()) > 0;

                // Cash return pending
                $cashReturns = (new Cashreturn())->findAll(
                    [
                        'driver_id = ?' => $driver->getId(),
                        'status = ?' => 'requested',
                    ]
                );

                $data['counters']['paymentHistory'] += $cashReturns->count();
            }

            $client = (new Client)->find($this->userId, 'customer_id');
            if ($client && $client->getId()) {
                $isPassenger = true;

                $rides = (new Request())->fetchPendingForClient($client->getId());

                $data['counters']['rides'] = $rides->count();
            }

            $data['vehicleWarning'] = $mustFillVehicle;
            $data['userType'] = $isDriver ? 'driver' : 'passenger';

            $payload = [
                'success' => true,
                'message' => __('Success'),
                'data' => $data,
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
    public function sendRequestAction()
    {
        try {
            $this->belongsToApp();

            $payload = [
                'success' => true,
                'message' => __('ACK OK send-request')
            ];

        } catch (\Exception $e) {
            $payload = [
                'error' => true,
                'message' => $e->getMessage()
            ];
        }

        $this->_sendJson($payload);
    }
}
