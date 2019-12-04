<?php

use Siberian\File;
use Siberian\Exception;
use Cabride\Model\Cabride;

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

            // Rebuild config
            $cabrideUser = (new \Api_Model_User())
                ->find('cabride', 'username');
            if ($cabrideUser->getId()) {
                $cabrideUser->delete();
            }

            Cabride::initApiUser($settings['cabride_server_auth'], $settings['cabride_server_port']);

            $logPath = path('var/log/modules/cabride.log');
            File::putContents($logPath, '-- RESTART -- \n');

            // Call for a restart!
            exec('pkill -9 node_64 2&>1');

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
            $offset = $request->getParam('offset', 0);
            $logPath = path('var/log/modules/cabride.log');

            if (!is_file($logPath)) {
                throw new Exception(p__('cabride', "Log file var/log/modules/cabride.log doesn't exists, seems your cabride server is not running."));
            }

            $logFile = fopen($logPath, 'rb');
            $txtContent = stream_get_contents($logFile, -1, $offset);

            $payload = [
                'success' => true,
                'txtContent' => $txtContent,
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
