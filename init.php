<?php

use Siberian\Api;
use Siberian\Service;
use Siberian\Assets;
use Siberian\Hook;
use Siberian_Module as Module;
use Cabride\Model\Cabride;

/**
 * @throws Exception
 */
function initApiUser () {
    Cabride::initApiUser();
}

/**
 * @param $payload
 * @return mixed
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
    return Cabride::populateExtended($context, $fields);
}

/**
 * @param $context
 * @param $fields
 * @return mixed
 * @throws Zend_Exception
 * @throws \Siberian\Exception
 */
function cabrideSaveExtended ($context, $fields) {
    return Cabride::saveExtended($context, $fields);
}

/** Alias for non-confusing escape */
class_alias("Cabride\Model\Service", "CabrideService");
class_alias("Cabride\Model\Cabride", "Cabride_Model_Cabride");

$init = function($bootstrap) {

    // Register API!
    Api::register("cabride", __("CabRide"), [
        "settings" => __("Settings"),
        "join-lobby" => __("Join lobby"),
        "send-request" => __("Send request"),
        "aggregate-information" => __("Aggregate information"),
    ]);

    // Registering cabride service
    Service::registerService("CabRide WebSocket", [
        "command" => "CabrideService::serviceStatus",
        "text" => "Running",
    ]);

    // Cab-Ride
    Assets::registerScss([
        "/app/local/modules/Cabride/features/cabride/scss/cabride.scss"
    ]);

    Module::addMenu("Cabride", "cabride", "Cabride",
        "cabride/backoffice_view", "icofont icofont-car");

    Hook::listen("mobile.controller.init", "cabride_extendedfields", "extendedFields");
    Hook::listen("editor.left.menu.ready", "cabride_nav", "dashboardNav");

    initApiUser();
};

