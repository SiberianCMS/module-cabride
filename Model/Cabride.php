<?php

namespace Cabride\Model;

use Core\Model\Base;
use CustomMenu\Model\Custom;
use Siberian\Account;
use Siberian\Api;
use Siberian\Exception;
use Siberian\File;
use Siberian\Hook;
use Siberian\Json;
use Siberian_Google_Geocoding as Geocoding;

/**
 * Class Cabride
 * @package Cabride\Model
 *
 * @method integer getId()
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
     * @var string
     */
    protected $_db_table = Db\Table\Cabride::class;

    /**
     * @param $valueId
     * @return array|bool
     */
    public function getInappStates($valueId)
    {
        $driver = "[" . p__("cabride", "Driver") . "] ";
        $passenger = "[" . p__("cabride", "Passenger") . "] ";

        $inAppStates = [
            [
                "state" => "cabride-home",
                "offline" => false,
                "params" => [],
                "childrens" => [
                    [
                        "label" => $passenger . p__("cabride", "Signup / Login"),
                        "state" => "cabride-signup-passenger",
                        "offline" => true,
                        "params" => [],
                    ],
                    [
                        "label" => $passenger . p__("cabride", "My rides"),
                        "state" => "cabride-my-rides",
                        "offline" => true,
                        "params" => [],
                    ],
                    [
                        "label" => $passenger . p__("cabride", "Payment history"),
                        "state" => "cabride-payment-history",
                        "offline" => true,
                        "params" => [],
                    ],
                    [
                        "label" => $driver . p__("cabride", "Signup / Login"),
                        "state" => "cabride-signup-driver",
                        "offline" => true,
                        "params" => [],
                    ],
                    [
                        "label" => $driver . p__("cabride", "My payments"),
                        "state" => "cabride-my-payments",
                        "offline" => true,
                        "params" => [],
                    ],
                    [
                        "label" => $driver . p__("cabride", "Pending requests"),
                        "state" => "cabride-pending-requests",
                        "offline" => true,
                        "params" => [],
                    ],
                    [
                        "label" => $driver . p__("cabride", "Accepted requests"),
                        "state" => "cabride-accepted-requests",
                        "offline" => true,
                        "params" => [],
                    ],
                    [
                        "label" => $driver . p__("cabride", "Completed rides"),
                        "state" => "cabride-completed-rides",
                        "offline" => true,
                        "params" => [],
                    ],
                    [
                        "label" => $driver . p__("cabride", "Cancelled rides"),
                        "state" => "cabride-cancelled",
                        "offline" => true,
                        "params" => [],
                    ],
                    [
                        "label" => $driver . p__("cabride", "Vechicle information"),
                        "state" => "cabride-vehicle-information",
                        "offline" => true,
                        "params" => [],
                    ],
                ],
            ],
        ];

        return $inAppStates;
    }

    /**
     * GET Feature url for app init
     *
     * @param $optionValue
     * @return array
     */
    public function getAppInitUris($optionValue)
    {
        $featureUrl = __url("/cabride/mobile_home/index");
        $featurePath = __path("/cabride/mobile_home/index");

        return [
            "featureUrl" => $featureUrl . "/index",
            "featurePath" => $featurePath . "/index",
        ];
    }

    /**
     * @return int|null
     * @throws Exception
     * @throws \Zend_Exception
     */
    public static function getCurrentValueId()
    {
        $app = (new self())->getApplication();
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
     * @throws \Zend_Exception
     */
    public static function getCurrent()
    {
        $app = (new self())->getApplication();
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
     * @throws \Zend_Exception
     */
    public static function dashboardNav($editorTree)
    {
        $app = (new self())->getApplication();
        $options = $app->getOptions();

        $useCabRide = false;
        foreach ($options as $option) {
            if ($option->getCode() === 'cabride') {
                $useCabRide = true;
                break;
            }
        }

        if (!$useCabRide) {
            return $editorTree;
        }

        $currentUrl = str_replace((new self())->getBaseUrl(), "", (new self())->getCurrentUrl());
        $editorAccess = [
            "cabride_dashboard",
            "cabride_form",
            "cabride_users",
            "cabride_drivers",
            "cabride_rides",
            "cabride_payments",
            "cabride_vehicle_types",
            "cabride_translations",
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
                "form" => [
                    "hasChilds" => false,
                    "isVisible" => self::_canAccess("cabride_form"),
                    "label" => p__("cabride", "Form"),
                    "icon" => "fa fa-list",
                    "url" => self::_getUrl("cabride/dashboard/form"),
                    "is_current" => ("/cabride/dashboard/form" === $currentUrl),
                ],
                "translations" => [
                    "hasChilds" => false,
                    "isVisible" => self::_canAccess("cabride_translations"),
                    "label" => p__("cabride", "Translations"),
                    "icon" => "fa fa-language",
                    "url" => self::_getUrl("cabride/dashboard/translations"),
                    "is_current" => ("/cabride/dashboard/translations" === $currentUrl),
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
     * @param null $auth
     * @param null $port
     * @throws \Exception
     */
    public static function initApiUser($auth = null, $port = null)
    {
        // Defaults!
        $serverAuth = $auth ?? __get('cabride_server_auth');
        if (empty($serverAuth)) {
            __set('cabride_server_auth', 'basic');
            $serverAuth = 'basic';
        }

        $serverPort = $port ?? __get('cabride_server_port');
        if (empty($serverPort)) {
            __set('cabride_server_port', 37000);
            $serverPort = 37000;
        }

        /**
         * @var $cabrideUser \Api_Model_User
         */
        $cabrideUser = (new \Api_Model_User())
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

        $mainDomain = __get('main_domain');

        if (!$cabrideUser->getId()) {
            // Create API User with full access
            $password = uniqid('cr', true) . 'api';
            $cabrideUser
                ->setUsername('cabride')
                ->setPassword($password)
                ->setBearerToken($cabrideUser->_generateBearerToken())
                ->setIsVisible(0)
                ->setAcl(Json::encode($acl))
                ->save();

            // Save Credentials for cabride server
            $serverHost = sprintf(
                'https://%s',
                $mainDomain
            );

            $wssHost = sprintf(
                'wss://%s',
                $mainDomain
            );

            $configFile = path('/app/local/modules/Cabride/resources/server/config.json');
            $config = [
                'apiUrl' => $serverHost,
                'wssHost' => $wssHost,
                'port' => $serverPort,
                'username' => 'cabride',
                'password' => base64_encode($password),
                'auth' => $serverAuth, // Defaults to basic
                'bearer' => $cabrideUser->getBearerToken()
            ];
            File::putContents($configFile, Json::encode($config));
        } else {
            // Update ACL to full access after any updates, in case there is new API Endpoints
            $cabrideUser
                ->setIsVisible(0)
                ->setAcl(Json::encode($acl))
                ->save();
        }
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
        $aclList = \Admin_Controller_Default::_sGetAcl();
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
