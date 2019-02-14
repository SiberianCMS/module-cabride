<?php

namespace Cabride\Model;

use Core\Model\Base;

/**
 * Class Driver
 * @package Cabride\Model
 *
 * @method Db\Table\Driver getTable();
 * @method float getBaseFare()
 * @method float getDistanceFare()
 * @method float getTimeFare()
 */
class Driver extends Base
{
    /**
     * Driver constructor.
     * @param array $params
     * @throws \Zend_Exception
     */
    public function __construct($params = [])
    {
        parent::__construct($params);
        $this->_db_table = 'Cabride\Model\Db\Table\Driver';
        return $this;
    }

    /**
     * @param $km
     * @param $minute
     * @return float
     */
    public function estimatePricing($km, $minute)
    {
        $base = $this->getBaseFare();
        $distance = $this->getDistanceFare();
        $time = $this->getTimeFare();

        $rawPrice = $base + ($distance * $km) + ($time * $minute);

        return round($rawPrice, 2, PHP_ROUND_HALF_UP);
    }

    /**
     * @param $valueId
     * @return mixed
     */
    public function fetchForValueId($valueId)
    {
        return $this->getTable()->fetchForValueId($valueId);
    }

    /**
     * @param $valueId
     * @param $formula
     * @return Driver[]
     */
    public function findNearestOnline($valueId, $formula)
    {
        return $this->getTable()->findNearestOnline($valueId, $formula);
    }
}