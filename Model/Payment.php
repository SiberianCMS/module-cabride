<?php

namespace Cabride\Model;

use Core\Model\Base;
use Siberian\Exception;

/**
 * Class Payment
 * @package Cabride\Model
 *
 * @method Db\Table\Payment getTable()
 */
class Payment extends Base
{
    /**
     * Payment constructor.
     * @param array $params
     * @throws \Zend_Exception
     */
    public function __construct($params = [])
    {
        parent::__construct($params);
        $this->_db_table = 'Cabride\Model\Db\Table\Payment';
        return $this;
    }

    /**
     * @return $this
     * @throws Exception
     * @throws \Zend_Exception
     */
    public function addCommission ()
    {
        $cabride = (new Cabride())->find($this->getValueId(), "value_id");
        if (!$cabride->getId()) {
            throw new Exception(p__("cabride", "We are unable to find feature settings."));
        }

        $commissionType = $cabride->getCommissionType();
        $commission = (float) $cabride->getCommission();
        switch ($commissionType) {
            case "disabled":
                $this
                    ->setCommissionType("disabled")
                    ->setCommissionAmount(0)
                    ->save();
                break;
            case "fixed":
                $this
                    ->setCommissionType("fixed")
                    ->setCommissionAmount($commission)
                    ->save();
                break;
            case "percentage":
                $total = $this->getAmount();
                $part = round($total / 100 * $commission, 2);

                $this
                    ->setCommissionType("percentage")
                    ->setCommissionAmount($part)
                    ->save();
                break;
        }

        return $this;
    }

    /**
     * @param $valueId
     * @param $params
     * @return mixed
     */
    public function aggregateCashReturn ($valueId, $params)
    {
        return $this->getTable()->aggregateCashReturn($valueId, $params);
    }

    /**
     * @param $driverId
     * @param $statuses
     * @param $params
     * @return mixed
     */
    public function cashReturnForDriverId ($driverId, $statuses = ["toreturn"], $params = null)
    {
        return $this->getTable()->cashReturnForDriverId($driverId, $statuses, $params);
    }

    /**
     * @param $valueId
     * @param $params
     * @return mixed
     */
    public function aggregatePayout ($valueId, $params)
    {
        return $this->getTable()->aggregatePayout($valueId, $params);
    }

    /**
     * @param $driverId
     * @param $statuses
     * @param $params
     * @return mixed
     */
    public function payoutForDriverId ($driverId, $statuses = ["unpaid"], $params = null)
    {
        return $this->getTable()->payoutForDriverId($driverId, $statuses, $params);
    }

    /**
     * @param $valueId
     * @return mixed
     */
    public function aggregateDashboard ($valueId)
    {
        return $this->getTable()->aggregateDashboard($valueId);
    }

    /**
     * @param $clientId
     * @return mixed
     */
    public function fetchForClientId ($clientId)
    {
        return $this->getTable()->fetchForClientId($clientId);
    }
}