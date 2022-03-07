<?php

use Siberian\Api;
use Siberian\Service;
use Siberian\Assets;
use Siberian\Hook;
use Siberian\Translation;
use Siberian_Module as Module;
use Cabride\Model\Cabride;
use Cabride\Model\Translation as CabrideTranslation;
use Cabride\Model\Service as Cabride_Service;

/** Alias for non-confusing escape */
class_alias('Cabride\Model\Cabride', 'Cabride_Model_Cabride');

/**
 * @throws Exception
 */
function initApiUser () {
    Cabride::initApiUser();
}

/**
 * @param $payload
 * @return mixed
 * @throws \Siberian\Exception
 */
function dashboardNav ($payload) {
    return Cabride::dashboardNav($payload);
}

/**
 * @return bool
 */
function reloadSocket () {
    return Cabride_Service::killServer();
}

/**
 * @param $payload
 * @return mixed
 */
function cabrideOverrideAppTranslations ($payload) {
    return CabrideTranslation::overrideApp($payload);
}

/**
 * @param $payload
 * @return mixed
 */
function cabrideOverrideEditorTranslations ($payload) {
    return CabrideTranslation::overrideEditor($payload);
}

$init = static function ($bootstrap) {

    if (method_exists(PaymentMethod\Model\Gateway::class, 'use')) {
        PaymentMethod\Model\Gateway::use('cash');
        PaymentMethod\Model\Gateway::use('stripe');
    }

    // Register API!
    Api::register('cabride', __('CabRide'), [
        'settings' => __('Settings'),
        'join-lobby' => __('Join lobby'),
        'send-request' => __('Send request'),
        'aggregate-information' => __('Aggregate information'),
    ]);

    // Registering cabride service
    Service::registerService('CabRide WebSocket', [
        'command' => '\Cabride\Model\Service::serviceStatus',
        'text' => 'Running',
    ]);

    // Cab-Ride
    Assets::registerScss([
        '/app/local/modules/Cabride/features/cabride/scss/cabride.scss'
    ]);

    Module::addMenu('Cabride', 'cabride', 'Cabride',
        'cabride/backoffice_view', 'icofont icofont-car');

    Translation::registerExtractor('cabride', 'Cabride');

    Hook::listen('editor.left.menu.ready', 'cabride_nav', 'dashboardNav');
    Hook::listen('ssl.certificate.update', 'cabride_reload_socket', 'reloadSocket');
    Hook::listen('app.translation.ready', 'cabride_app_translation', 'cabrideOverrideAppTranslations');
    Hook::listen('editor.translation.ready', 'cabride_editor_translation', 'cabrideOverrideEditorTranslations');

    // Be sure the config.json is always present*
    initApiUser();

    // searching for enterprise payment stripe.js file.
    $conflictStripeFile = path('/app/local/modules/Enterprisepayment/features/enterprisepayment/js/stripe.js');
    if (is_readable($conflictStripeFile)) {
        unlink($conflictStripeFile);
    }
};

