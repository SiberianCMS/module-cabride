<?php

/**
 * Class Cabride_Mobile_ViewController
 */
class Cabride_Mobile_ViewController extends Application_Controller_Mobile_Default
{

    /**
     *
     */
    public function fetchsettingsAction()
    {
        try {
            // Fetch installation config file!
            $configFile = Core_Model_Directory::getBasePathTo(
                '/app/local/modules/Cabride/resources/server/config.json'
            );

            if (!file_exists($configFile)) {
                throw new \Siberian\Exception(__('The configuration files is missing!'));
            }

            $config = \Siberian_Json::decode(file_get_contents($configFile));

            $wssUrl = $config['wssHost'] . ':' . $config['port'] . '/cabride';

            $payload = [
                'success' => true,
                'settings' => [
                    'wssUrl' => $wssUrl
                ]
            ];

        } catch (\Exception $e) {
            $payload = [
                'error' => true,
                'message' => __('An unknown error occurred, please try again later.')
            ];
        }

        $this->_sendJson($payload);
    }
}
