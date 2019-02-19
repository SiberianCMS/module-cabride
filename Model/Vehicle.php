<?php

namespace Cabride\Model;

use Core\Model\Base;

/**
 * Class Vehicle
 * @package Cabride\Model
 *
 * @method string getIcon()
 * @method float getBaseFare()
 * @method float getDistanceFare()
 * @method float getTimeFare()
 */
class Vehicle extends Base
{
    /**
     * Vehicle constructor.
     * @param array $params
     * @throws \Zend_Exception
     */
    public function __construct($params = [])
    {
        parent::__construct($params);
        $this->_db_table = "Cabride\Model\Db\Table\Vehicle";
        return $this;
    }

    /**
     * @return string
     */
    public function getIconUri()
    {
        $icon = trim($this->getIcon());
        $iconPath = !empty($icon) ?
            "/images/application/" . $this->getIcon() :
            "/app/local/modules/Cabride/resources/design/desktop/flat/images/car-icon.png";

        return $iconPath;
    }

    /**
     * @param $km
     * @param $minute
     * @param bool $format
     * @return float|mixed
     * @throws \Zend_Currency_Exception
     * @throws \Zend_Exception
     */
    public function estimatePricing($km, $minute, $format = true)
    {
        $base = $this->getBaseFare();
        $distance = $this->getDistanceFare();
        $time = $this->getTimeFare();

        $rawPrice = $base + ($distance * $km) + ($time * $minute);
        $price = round($rawPrice, 2, PHP_ROUND_HALF_UP);

        if ($format) {
            return self::_formatPrice($price);
        }
        return $price;
    }
}