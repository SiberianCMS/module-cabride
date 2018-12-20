<?php
$initializeApiUser = function () {
    $cabrideUser = (new Api_Model_User())
        ->find('cabride', 'username');

    $acl = [];
    foreach (Siberian_Api::$acl_keys as $key => $subkeys) {
        // Filter only chatroom API endpoints
        if ($key === 'cabride') {
            if (!isset($acl[$key])) {
                $acl[$key] = [];
            }

            if (is_array($acl[$key])) {
                foreach ($subkeys as $subkey => $subvalue) {
                    if (!array_key_exists($subkey, $acl[$key])) {
                        $acl[$key][$subkey] = true;
                    }
                }
            }
        }
    }

    if (!$cabrideUser->getId()) {
        // Create API User with full access
        $password = 'cr' . uniqid() . 'api';
        $cabrideUser
            ->setUsername('cabride')
            ->setPassword($password)
            ->setIsVisible(0)
            ->setAcl(Siberian_Json::encode($acl))
            ->save();

        // Save Credentials for chatrooms server
        $serverHost = sprintf(
            '%s://%s',
            $_SERVER['REQUEST_SCHEME'],
            explode(':', $_SERVER['HTTP_HOST'])[0]
        );

        $wssHost = sprintf(
            'wss://%s',
            explode(':', $_SERVER['HTTP_HOST'])[0]
        );

        $configFile = Core_Model_Directory::getBasePathTo('/app/local/modules/Cabride/resources/server/config.json');
        $config = [
            'apiUrl' => $serverHost,
            'wssHost' => $wssHost,
            'port' => 37000,
            'username' => 'cabride',
            'password' => base64_encode($password)
        ];
        file_put_contents($configFile, Siberian_Json::encode($config));

    } else {
        // Update ACL to full access after any updates, in case there is new API Endpoints
        $cabrideUser
            ->setIsVisible(0)
            ->setAcl(Siberian_Json::encode($acl))
            ->save();
    }
};

$init = function($bootstrap) use ($initializeApiUser) {

    // Register API!
    \Siberian_Api::register('cabride', __('CabRide'), [
        'settings' => __('Settings'),
        'join-lobby' => __('Join lobby'),
        'send-request' => __('Send request'),
    ]);

    // Registering realtimechat service
    \Siberian_Service::registerService('CabRide uWS', [
        'command' => 'Cabride_Model_Service::serviceStatus',
        'text' => 'Running',
    ]);

    // Cab-Ride
    \Siberian_Assets::registerScss([
        "/app/local/modules/Cabride/features/cabride/scss/cabride.scss"
    ]);

    $initializeApiUser();
};

