<?php

/**
 * Class Cabride_Model_Cabride
 */
class Cabride_Model_Cabride extends Core_Model_Default
{
    /**
     *  constructor.
     * @param array $params
     */
    public function __construct($params = [])
    {
        parent::__construct($params);
        $this->_db_table = 'Cabride_Model_Db_Table_Cabride';
        return $this;
    }

    /**
     * @param $editorTree
     * @return mixed
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
            'label' => __('Cab-Ride'),
            'id' => 'sidebar-left-group-cabride',
            'is_current' => false,
            'url' => self::getUrl('/'),
            'icon' => 'fa fa-taxi',
            'childs' => [
                'dashboard' => [
                    'hasChilds' => false,
                    'isVisible' => self::_canAccess("cabride_dashboard"),
                    'label' => __('Dashboard'),
                    'icon' => 'fa fa-home',
                    'url' => self::getUrl('cabride/dashboard/index'),
                    'is_current' => ('/cabride/dashboard' === $currentUrl),
                ],
                'rides' => [
                    'hasChilds' => false,
                    'isVisible' => self::_canAccess("cabride_rides"),
                    'label' => __('Rides'),
                    'icon' => 'icon ion-cab-car',
                    'url' => self::getUrl('cabride/dashboard/rides'),
                    'is_current' => ('/cabride/dashboard/rides' === $currentUrl),
                ],
                'payments' => [
                    'hasChilds' => false,
                    'isVisible' => self::_canAccess("cabride_payments"),
                    'label' => __('Payments'),
                    'icon' => 'fa fa-credit-card',
                    'url' => self::getUrl('cabride/dashboard/payments'),
                    'is_current' => ('/cabride/dashboard/payments' === $currentUrl),
                ],
                'users' => [
                    'hasChilds' => false,
                    'isVisible' => self::_canAccess("cabride_users"),
                    'label' => __('Users'),
                    'icon' => 'fa fa-users',
                    'url' => self::getUrl('cabride/dashboard/users'),
                    'is_current' => ('/cabride/dashboard/users' === $currentUrl),
                ],
                'drivers' => [
                    'hasChilds' => false,
                    'isVisible' => self::_canAccess("cabride_drivers"),
                    'label' => __('Drivers'),
                    'icon' => 'icon ion-cab-wheel',
                    'url' => self::getUrl('cabride/dashboard/drivers'),
                    'is_current' => ('/cabride/dashboard/drivers' === $currentUrl),
                ],
                'vehicle_types' => [
                    'hasChilds' => false,
                    'isVisible' => self::_canAccess("cabride_vehicle_types"),
                    'label' => __('Vehicle types'),
                    'icon' => 'fa fa-car',
                    'url' => self::getUrl('cabride/dashboard/vehicle-types'),
                    'is_current' => ('/cabride/dashboard/vehicle-types' === $currentUrl),
                ],
                'settings' => [
                    'hasChilds' => false,
                    'isVisible' => self::_canAccess("cabride_settings"),
                    'label' => __('Settings'),
                    'icon' => 'fa fa-sliders',
                    'url' => self::getUrl('cabride/dashboard/settings'),
                    'is_current' => ('/cabride/dashboard/settings' === $currentUrl),
                ],
            ],
        ];

        return $editorTree;
    }

    /**
     * @param array $acl
     * @return bool
     */
    protected function _canAccessAnyOf ($acl = [])
    {
        return true;
    }

    /**
     * @param $acl
     * @return bool
     */
    protected function _canAccess ($acl)
    {
        return true;
    }

    /**
     * @param string $url
     * @param array $params
     * @param null $locale
     * @return array|mixed|string
     */
    public function getUrl ($url = "", array $params = [], $locale = null)
    {
        return __url($url);
    }
}