<?php

namespace Cabride\Model;

use Core\Model\Base;
use Siberian\Exception;

/**
 * Class Payment
 * @package Cabride\Model
 *
 * @method Db\Table\Payment getTable()
 * @method integer getId()
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
        $this->_db_table = Db\Table\Payment::class;
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
        $commissionFixed = (float) $cabride->getCommissionFixed();
        switch ($commissionType) {
            case "disabled":
                $this
                    ->setCommissionType("disabled")
                    ->setCommissionAmount(0)
                    ->setCommissionExceedAmount(false)
                    ->save();
                break;
            case "fixed":
                $comValue = round($commissionFixed, 2);
                $commissionExceedAmount = false;
                if ($comValue > $this->getAmount()) {
                    $comValue = $this->getAmount();
                    $commissionExceedAmount = true;
                }

                $this
                    ->setCommissionType("fixed")
                    ->setCommissionAmount($comValue)
                    ->setCommissionExceedAmount($commissionExceedAmount)
                    ->save();
                break;
            case "percentage":
                $total = $this->getAmount();
                $part = round($total / 100 * $commission, 2);

                $commissionExceedAmount = false;
                if ($part > $total) {
                    $part = $this->getAmount();
                    $commissionExceedAmount = true;
                }

                $this
                    ->setCommissionType("percentage")
                    ->setCommissionAmount($part)
                    ->setCommissionExceedAmount($commissionExceedAmount)
                    ->save();
                break;
            case "mixed":
                $total = $this->getAmount();
                $part = round(($total / 100 * $commission) + $commissionFixed, 2);

                $commissionExceedAmount = false;
                if ($part > $total) {
                    $part = $this->getAmount();
                    $commissionExceedAmount = true;
                }

                $this
                    ->setCommissionType("mixed")
                    ->setCommissionAmount($part)
                    ->setCommissionExceedAmount($commissionExceedAmount)
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