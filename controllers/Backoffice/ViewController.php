<?php

use Siberian\File;
use Siberian\Exception;
use Cabride\Model\Cabride;
use Cabride\Model\Service;

/**
 * Class Cabride_Backoffice_ViewController
 */
class Cabride_Backoffice_ViewController extends Backoffice_Controller_Default
{
    /**
     *
     */
    public function loadAction()
    {
        $payload = [
            'title' => sprintf('%s > %s > %s',
                __('Manage'), __('Modules'), p__('cabride', 'Cabride')),
            'icon' => 'icofont icofont-car',
            'settings' => [
                'cabride_server_auth' => __get('cabride_server_auth'),
                'cabride_server_port' => (integer) __get('cabride_server_port'),
                'cabride_self_serve' => __get('cabride_self_serve'),
            ],
        ];

        $this->_sendJson($payload);
    }

    /**
     *
     */
    public function saveAction()
    {
        try {
            $request = $this->getRequest();
            $settings = $request->getBodyParams();

            if (!in_array($settings['cabride_server_auth'], ['basic', 'bearer'])) {
                throw new Exception(p__('cabride', 'Auth type is invalid and must be either basic or bearer'));
            }

            if ($settings['cabride_server_port'] < 0 ||
                $settings['cabride_server_port'] > 65536) {
                throw new Exception(p__('cabride', 'Port is invalid and must be between 0 and 65536'));
            }

            __set('cabride_server_auth', $settings['cabride_server_auth']);
            __set('cabride_server_port', $settings['cabride_server_port']);
            __set('cabride_self_serve', $settings['cabride_self_serve']);

            // Rebuild config
            $cabrideUser = (new \Api_Model_User())
                ->find('cabride', 'username');
            if ($cabrideUser->getId()) {
                $cabrideUser->delete();
            }

            Cabride::initApiUser($settings['cabride_server_auth'], $settings['cabride_server_port']);

            $logPath = path('var/log/cabride.log');
            File::putContents($logPath, '-- RESTART -- \n');

            // Call for a restart!
            Service::killServer();

            $payload = [
                'success' => true,
                'message' => p__('cabride', 'Settings saved, WebSocket is restarting.')
            ];

        } catch (\Exception $e) {
            $payload = [
                'error' => true,
                'message' => $e->getMessage()
            ];
        }

        $this->_sendJson($payload);
    }

    public function liveLogAction ()
    {
        try {
            $request = $this->getRequest();
            $offset = (int) $request->getParam('offset', 0);
            $logPath = path('var/log/cabride.log');

            if (!is_file($logPath)) {
                throw new Exception(p__('cabride', "Log file var/log/cabride.log doesn't exists, seems your cabride server is not running."));
            }

            $logFile = fopen($logPath, 'rb');
            if ($offset === 0) {
                $fsize = filesize($logPath);
                if ($fsize > 10000) {
                    $offset = $fsize - 5000;
                }
            }
            $txtContent = stream_get_contents($logFile, -1, $offset);

            $payload = [
                'success' => true,
                'txtContent' => explode("\n", $txtContent),
                'offset' => strlen($txtContent) + $offset,
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
