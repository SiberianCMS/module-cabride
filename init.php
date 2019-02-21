<?php

use Siberian\Json;
use Siberian\Api;
use Siberian\Service;
use Siberian\Assets;
use Siberian\Hook;
use Cabride\Model\Cabride;
use Cabride\Model\Driver;

$initializeApiUser = function () {
    $cabrideUser = (new Api_Model_User())
        ->find('cabride', 'username');

    $acl = [];
    foreach (Api::$acl_keys as $key => $subkeys) {
        // Filter only cabride API endpoints
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
            ->setAcl(Json::encode($acl))
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

        $configFile = path('/app/local/modules/Cabride/resources/server/config.json');
        $config = [
            'apiUrl' => $serverHost,
            'wssHost' => $wssHost,
            'port' => 37000,
            'username' => 'cabride',
            'password' => base64_encode($password)
        ];
        file_put_contents($configFile, Json::encode($config));

    } else {
        // Update ACL to full access after any updates, in case there is new API Endpoints
        $cabrideUser
            ->setIsVisible(0)
            ->setAcl(Json::encode($acl))
            ->save();
    }
};

/**
 * @param $payload
 * @return mixed
 * @throws \Siberian\Exception
 */
function dashboardNav ($payload) {
    return Cabride::dashboardNav($payload);
}

/**
 * @param $payload
 * @return mixed
 * @throws Zend_Exception
 * @throws \Siberian\Exception
 */
function extendedFields ($payload) {
    return Cabride::extendedFields($payload);
};

/**
 * @param $context
 * @param $fields
 * @return mixed
 * @throws Zend_Exception
 */
function cabridePopulateExtended ($context, $fields) {
    return Driver::populateExtended($context, $fields);
}

/**
 * @param $context
 * @param $fields
 * @return mixed
 * @throws Zend_Exception
 */
function cabrideSaveExtended ($context, $fields) {
    return Driver::saveExtended($context, $fields);
}

$init = function($bootstrap) use ($initializeApiUser) {

    // Register API!
    Api::register("cabride", __("CabRide"), [
        "settings" => __("Settings"),
        "join-lobby" => __("Join lobby"),
        "send-request" => __("Send request"),
    ]);

    // Registering cabride service
    Service::registerService("CabRide WebSocket", [
        "command" => "Cabride\\Model\\Service::serviceStatus",
        "text" => "Running",
    ]);

    // Cab-Ride
    Assets::registerScss([
        "/app/local/modules/Cabride/features/cabride/scss/cabride.scss"
    ]);

    Hook::listen("app.init", "cabride_extendedfields", "extendedFields");
    Hook::listen("mobile.controller.init", "cabride_extendedfields", "extendedFields");
    Hook::listen("editor.left.menu.ready", "cabride_nav", "dashboardNav");

    $initializeApiUser();
};

