<?php

namespace Cabride\Model;

use Core\Model\Base;
use Siberian\Account;
use Siberian\Exception;
use Siberian\Json;
use Siberian_Google_Geocoding as Geocoding;

/**
 * Class Cabride
 * @package Cabride\Model
 *
 * @method $this find($id, $field = null)
 * @method string getDistanceUnit()
 * @method integer getSearchRadius()
 */
class Cabride extends Base
{
    /**
     * @var null
     */
    public static $acl = null;

    /**
     * Cabride constructor.
     * @param array $params
     * @throws \Zend_Exception
     */
    public function __construct($params = [])
    {
        parent::__construct($params);
        $this->_db_table = 'Cabride\Model\Db\Table\Cabride';
        return $this;
    }

    /**
     * @return int|null
     * @throws Exception
     */
    public static function getCurrentValueId()
    {
        $app = self::getApplication();
        if ($app) {
            $options = $app->getOptions();
            foreach ($options as $option) {
                if ($option->getCode() === "cabride") {
                    return $option->getId();
                }
            }
        }
        return null;
    }

    /**
     * @return \Application_Model_Option_Value|null
     * @throws Exception
     */
    public static function getCurrent()
    {
        $app = self::getApplication();
        if ($app) {
            $options = $app->getOptions();
            foreach ($options as $option) {
                if ($option->getCode() === "cabride") {
                    return $option;
                }
            }
        }
        return null;
    }

    /**
     * @param $url
     * @param $matches
     * @return bool
     */
    public static function startsWith($url, $matches)
    {
        foreach ($matches as $match) {
            if (strpos($url, $match) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $editorTree
     * @return mixed
     * @throws Exception
     */
    public static function dashboardNav($editorTree)
    {
        $app = self::getApplication();
        $options = $app->getOptions();

        $useCabRide = false;
        foreach ($options as $option) {
            if ($option->getCode() === "cabride") {
                $useCabRide = true;
                break;
            }
        }

        if (!$useCabRide) {
            return $editorTree;
        }


        $currentUrl = str_replace(self::getBaseUrl(), "", self::getCurrentUrl());
        $editorAccess = [
            "cabride_dashboard",
            "cabride_users",
            "cabride_drivers",
            "cabride_rides",
            "cabride_payments",
            "cabride_vehicle_types",
            "cabride_settings",
        ];

        $editorTree["cabride"] = [
            "hasChilds" => true,
            "isVisible" => self::_canAccessAnyOf($editorAccess),
            "label" => p__("cabride", "Cab-Ride"),
            "id" => "sidebar-left-group-cabride",
            "is_current" => false,
            "url" => self::_getUrl("/"),
            "icon" => "fa fa-taxi",
            "childs" => [
                "dashboard" => [
                    "hasChilds" => false,
                    "isVisible" => self::_canAccess("cabride_dashboard"),
                    "label" => p__("cabride", "Dashboard"),
                    "icon" => "fa fa-home",
                    "url" => self::_getUrl("cabride/dashboard/index"),
                    "is_current" => ("/cabride/dashboard" === $currentUrl),
                ],
                "payments" => [
                    "hasChilds" => false,
                    "isVisible" => self::_canAccess("cabride_payments"),
                    "label" => p__("cabride", "Accountancy"),
                    "icon" => "fa fa-credit-card",
                    "url" => self::_getUrl("cabride/dashboard/payments"),
                    "is_current" => preg_match("#^/cabride/dashboard/payments#", $currentUrl),
                ],
                "rides" => [
                    "hasChilds" => false,
                    "isVisible" => self::_canAccess("cabride_rides"),
                    "label" => p__("cabride", "Rides"),
                    "icon" => "icon ion-cab-car",
                    "url" => self::_getUrl("cabride/dashboard/rides"),
                    "is_current" => ("/cabride/dashboard/rides" === $currentUrl),
                ],
                "users" => [
                    "hasChilds" => false,
                    "isVisible" => self::_canAccess("cabride_users"),
                    "label" => p__("cabride", "Passengers"),
                    "icon" => "fa fa-users",
                    "url" => self::_getUrl("cabride/dashboard/users"),
                    "is_current" => ("/cabride/dashboard/users" === $currentUrl),
                ],
                "drivers" => [
                    "hasChilds" => false,
                    "isVisible" => self::_canAccess("cabride_drivers"),
                    "label" => p__("cabride", "Drivers"),
                    "icon" => "icon ion-cab-wheel",
                    "url" => self::_getUrl("cabride/dashboard/drivers"),
                    "is_current" => self::startsWith($currentUrl, [
                        "/cabride/dashboard/drivers",
                        "/cabride/driver/edit",
                    ]),
                ],
                "vehicle_types" => [
                    "hasChilds" => false,
                    "isVisible" => self::_canAccess("cabride_vehicle_types"),
                    "label" => p__("cabride", "Vehicle types"),
                    "icon" => "fa fa-car",
                    "url" => self::_getUrl("cabride/dashboard/vehicle-types"),
                    "is_current" => self::startsWith($currentUrl, [
                        "/cabride/dashboard/vehicle-types",
                        "/cabride/vehicle/edit",
                    ]),
                ],
                "settings" => [
                    "hasChilds" => false,
                    "isVisible" => self::_canAccess("cabride_settings"),
                    "label" => p__("cabride", "Settings"),
                    "icon" => "fa fa-sliders",
                    "url" => self::_getUrl("cabride/dashboard/settings"),
                    "is_current" => ("/cabride/dashboard/settings" === $currentUrl),
                ],
            ],
        ];

        return $editorTree;
    }

    /**
     * @param $payload
     * @return mixed
     * @throws \Siberian\Exception
     * @throws \Zend_Exception
     */
    public static function extendedFields ($payload)
    {
        $application = $payload["application"];
        $request = $payload["request"];
        $session = $payload["session"];

        // Check if Cabride feature exists!
        $valueId = Cabride::getCurrentValueId();
        if ($valueId === null) {
            // Stops here!
            return $payload;
        }

        // Check if the user is a driver
        $customerId = $session->getCustomerId();
        $driver = (new Driver())->find($customerId, "customer_id");
        if ($driver->getId()) {
            // Stops here!
            // Add custom fields to my account!
            Account::addFields(
                "Cabride",
                [
                    [
                        "type" => "spacer",
                        "key" => "cabride_spacer",
                    ],
                    [
                        "type" => "divider",
                        "key" => "cabride_divider",
                        "label" => p__("cabride", "Driver information"),
                    ],
                    [
                        "type" => "text",
                        "key" => "driver_license",
                        "label" => p__("cabride", "Driving license"),
                    ],
                    [
                        "type" => "text",
                        "key" => "driver_phone",
                        "label" => p__("cabride", "Mobile number"),
                    ],
                    [
                        "type" => "textarea",
                        "key" => "base_address",
                        "rows" => "3",
                        "label" => p__("cabride", "Base address"),
                    ],
                    [
                        "type" => "number",
                        "key" => "pickup_radius",
                        "min" => "1",
                        "max" => "100",
                        "step" => "1",
                        "label" => p__("cabride", "Pickup radius"),
                    ]
                ],
                "cabridePopulateExtended",
                "cabrideSaveExtended");

            return $payload;
        }

        $client = (new Client())->find($customerId, "customer_id");
        if ($client->getId()) {
            // Stops here!
            // Add custom fields to my account!
            Account::addFields(
                "Cabride",
                [
                    [
                        "type" => "spacer",
                        "key" => "cabride_spacer",
                    ],
                    [
                        "type" => "divider",
                        "key" => "cabride_divider",
                        "label" => p__("cabride", "Passenger information"),
                    ],
                    [
                        "type" => "text",
                        "key" => "mobile",
                        "label" => p__("cabride", "Mobile"),
                    ],
                    [
                        "type" => "textarea",
                        "key" => "address",
                        "rows" => "3",
                        "label" => p__("cabride", "Address"),
                    ],
                ],
                "cabridePopulateExtended",
                "cabrideSaveExtended");

            return $payload;
        }

        return $payload;
    }

    /**
     * @param $context
     * @param $fields
     * @return mixed
     * @throws \Zend_Exception
     */
    public static function populateExtended ($context, $fields)
    {
        $session = $context["session"];
        $driver = (new Driver())->find($session->getCustomerId(), "customer_id");
        if ($driver->getId()) {
            foreach ($fields as &$field) {
                switch ($field["key"]) {
                    case "vehicle_model":
                        $field["value"] = $driver->getVehicleModel();
                        break;
                    case "vehicle_license_plate":
                        $field["value"] = $driver->getVehicleLicensePlate();
                        break;
                    case "driver_license":
                        $field["value"] = $driver->getDriverLicense();
                        break;
                    case "driver_phone":
                        $field["value"] = $driver->getDriverPhone();
                        break;
                    case "base_address":
                        $field["value"] = $driver->getBaseAddress();
                        break;
                    case "pickup_radius":
                        $field["value"] = $driver->getPickupRadius();
                        break;
                    case "driver_photo":
                        $field["value"] = $driver->getDriverPhoto();
                        break;
                }
            }
        }

        $client = (new Client())->find($session->getCustomerId(), "customer_id");
        if ($client->getId()) {
            foreach ($fields as &$field) {
                switch ($field["key"]) {
                    case "mobile":
                        $field["value"] = $client->getMobile();
                        break;
                    case "address":
                        $field["value"] = $client->getAddress();
                        break;
                }
            }
        }

        return $fields;
    }

    /**
     * @param $context
     * @param $fields
     * @return mixed
     * @throws Exception
     * @throws \Zend_Exception
     */
    public static function saveExtended ($context, $fields)
    {
        $application = $context["application"];
        $session = $context["session"];
        $driver = (new Driver())->find($session->getCustomerId(), "customer_id");
        if ($driver->getId()) {
            foreach ($fields as &$field) {
                switch ($field["key"]) {
                    case "vehicle_model":
                        $driver->setVehicleModel($field["value"]);
                        break;
                    case "vehicle_license_plate":
                        $driver->setVehicleLicensePlate($field["value"]);
                        break;
                    case "driver_license":
                        $driver->setDriverLicense($field["value"]);
                        break;
                    case "driver_phone":
                        $driver->setDriverPhone($field["value"]);
                        break;
                    case "base_address":
                        $driver->setBaseAddress($field["value"]);

                        $validated = Geocoding::validateAddress(["address" => $field["value"]], $application->getGooglemapsKey());
                        if (!$validated) {
                            throw new Exception(p__("cabride", "We are unable to validate your address!"));
                        }

                        $parts = Geocoding::rawToParts($validated->getRawResult());
                        $driver->setBaseAddressParts(Json::encode($parts));

                        break;
                    case "pickup_radius":
                        $driver->setPickupRadius($field["value"]);
                        break;
                    case "driver_photo":
                        $driver->setDriverPhoto($field["value"]);
                        break;
                }
            }
            $driver->save();
        }

        $client = (new Client())->find($session->getCustomerId(), "customer_id");
        if ($client->getId()) {
            foreach ($fields as &$field) {
                switch ($field["key"]) {
                    case "mobile":
                        $client->setMobile($field["value"]);
                        break;
                    case "address":
                        $client->setAddress($field["value"]);

                        $validated = Geocoding::validateAddress(["address" => $field["value"]], $application->getGooglemapsKey());
                        if (!$validated) {
                            throw new Exception(p__("cabride", "We are unable to validate your address!"));
                        }

                        $parts = Geocoding::rawToParts($validated->getRawResult());
                        $client->setAddressParts(Json::encode($parts));

                        break;
                }
            }
            $client->save();
        }

        return $fields;
    }

    /**
     * @param array $acl
     * @return bool
     */
    protected static function _canAccessAnyOf($acl = [])
    {
        foreach ($acl as $_acl) {
            if (self::_canAccess($_acl)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $acl
     * @return bool
     */
    protected static function _canAccess($acl)
    {
        $aclList = \Admin_Controller_Default::_getAcl();
        if ($aclList) {
            return $aclList->isAllowed($acl);
        }

        return true;
    }

    /**
     * @param string $url
     * @param array $params
     * @param null $locale
     * @return array|mixed|string
     */
    public static function _getUrl($url = "", array $params = [], $locale = null)
    {
        return __url($url);
    }
}