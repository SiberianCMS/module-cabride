<?php

namespace Cabride\Model;

use Core\Model\Base;
use Siberian\Account;

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
     * @return null
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
     * @throws \Siberian\Exception
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
     * @throws \Siberian\Exception
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
        $editorAccess = in_array($currentUrl, [
            "cabride_dashboard",
            "cabride_users",
            "cabride_drivers",
            "cabride_rides",
            "cabride_payments",
            "cabride_vehicle_types",
            "cabride_settings",
        ]);

        $editorTree['cabride'] = [
            'hasChilds' => true,
            'isVisible' => self::_canAccessAnyOf($editorAccess),
            'label' => p__("cabride", 'Cab-Ride'),
            'id' => 'sidebar-left-group-cabride',
            'is_current' => false,
            'url' => self::_getUrl('/'),
            'icon' => 'fa fa-taxi',
            'childs' => [
                'dashboard' => [
                    'hasChilds' => false,
                    'isVisible' => self::_canAccess("cabride_dashboard"),
                    'label' => p__("cabride", "Dashboard"),
                    'icon' => 'fa fa-home',
                    'url' => self::_getUrl('cabride/dashboard/index'),
                    'is_current' => ('/cabride/dashboard' === $currentUrl),
                ],
                'rides' => [
                    'hasChilds' => false,
                    'isVisible' => self::_canAccess("cabride_rides"),
                    'label' => p__("cabride", 'Rides'),
                    'icon' => 'icon ion-cab-car',
                    'url' => self::_getUrl('cabride/dashboard/rides'),
                    'is_current' => ('/cabride/dashboard/rides' === $currentUrl),
                ],
                'payments' => [
                    'hasChilds' => false,
                    'isVisible' => self::_canAccess("cabride_payments"),
                    'label' => p__("cabride", 'Payments'),
                    'icon' => 'fa fa-credit-card',
                    'url' => self::_getUrl('cabride/dashboard/payments'),
                    'is_current' => ('/cabride/dashboard/payments' === $currentUrl),
                ],
                'users' => [
                    'hasChilds' => false,
                    'isVisible' => self::_canAccess("cabride_users"),
                    'label' => p__("cabride", 'Users'),
                    'icon' => 'fa fa-users',
                    'url' => self::_getUrl('cabride/dashboard/users'),
                    'is_current' => ('/cabride/dashboard/users' === $currentUrl),
                ],
                'drivers' => [
                    'hasChilds' => false,
                    'isVisible' => self::_canAccess("cabride_drivers"),
                    'label' => p__("cabride", 'Drivers'),
                    'icon' => 'icon ion-cab-wheel',
                    'url' => self::_getUrl('cabride/dashboard/drivers'),
                    'is_current' => self::startsWith($currentUrl, [
                        '/cabride/dashboard/drivers',
                        '/cabride/driver/edit',
                    ]),
                ],
                'vehicle_types' => [
                    'hasChilds' => false,
                    'isVisible' => self::_canAccess("cabride_vehicle_types"),
                    'label' => p__("cabride", 'Vehicle types'),
                    'icon' => 'fa fa-car',
                    'url' => self::_getUrl('cabride/dashboard/vehicle-types'),
                    'is_current' => self::startsWith($currentUrl, [
                        '/cabride/dashboard/vehicle-types',
                        '/cabride/vehicle/edit',
                    ]),
                ],
                'settings' => [
                    'hasChilds' => false,
                    'isVisible' => self::_canAccess("cabride_settings"),
                    'label' => p__("cabride", 'Settings'),
                    'icon' => 'fa fa-sliders',
                    'url' => self::_getUrl('cabride/dashboard/settings'),
                    'is_current' => ('/cabride/dashboard/settings' === $currentUrl),
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
        if (!$driver->getId()) {
            // Stops here!
            return $payload;
        }

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
                    "label" => p__("cabride", "Driver informations"),
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

    /**
     * @param array $acl
     * @return bool
     */
    protected static function _canAccessAnyOf($acl = [])
    {
        return true;
    }

    /**
     * @param $acl
     * @return bool
     */
    protected static function _canAccess($acl)
    {
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