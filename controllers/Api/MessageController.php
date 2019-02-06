<?php

/**
 * Class Cabride_Api_MessageController
 */
class Cabride_Api_MessageController extends Cabride_Controller_Default {

    /**
     * @var array
     */
    public $secured_actions = [
        'settings',
        'join-lobby',
        'send-request',
    ];

    /**
     * Fetch settings & ssl certificates to run wss://
     */
    public function settingsAction() {
        try {
            /**
             * @var $sslCertificate System_Model_SslCertificates
             */
            $sslCertificate = (new System_Model_SslCertificates())
                ->find([
                    'hostname' => $this->getRequest()->getHttpHost()
                ]);
            if (!$sslCertificate->getId()) {
                throw new \Siberian\Exception(__('Unable to find a corresponding SSL Certificate!'));
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
                'message' => __('An unknown error occurred, please try again later.')
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     * User must join the lobby before any action!
     */
    public function joinLobbyAction() {
        try {
            $this->belongsToApp();

            $payload =[
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
    public function sendRequestAction() {
        try {
            $this->belongsToApp();

            $params = $this->params;

            $payload = [
                'success' => true,
                'message' => __("ACK OK send-request")
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
