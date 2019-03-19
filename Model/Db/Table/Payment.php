<?php

namespace Cabride\Model\Db\Table;

use Core_Model_Db_Table;

/**
 * Class Payment
 * @package Cabride\Model\Db\Table
 */
class Payment extends Core_Model_Db_Table
{
    /**
     * @var string
     */
    protected $_name = "cabride_payment";

    /**
     * @var string
     */
    protected $_primary = "payment_id";

    /**
     * @param $valueId
     * @return mixed
     */
    public function aggregateCashReturn ($valueId)
    {
        $select = $this->_db->select()
            ->from(
                ["p" => $this->_name],
                [
                    "*",
                    "total" => new \Zend_Db_Expr("SUM(commission_amount)"),
                    "payment_ids" => new \Zend_Db_Expr("GROUP_CONCAT(payment_id)"),
                    "period_from" => new \Zend_Db_Expr("MIN(p.created_at)"),
                    "period_to" => new \Zend_Db_Expr("MAX(p.created_at)")
                ]
            )
            ->joinInner(
                ["d" => "cabride_driver"],
                "d.driver_id = p.driver_id",
                []
            )
            ->joinInner(
                ["c" => "customer"],
                "c.customer_id = d.customer_id",
                [
                    "firstname",
                    "lastname",
                    "email",
                ]
            )
            ->where("p.value_id = ?", $valueId)
            ->where("p.return_status = ?", "toreturn")
            ->where("p.method = ?", "cash")
            ->having("total > 0") // Total must greater than 0!
            ->group("driver_id")
        ;

        return $this->toModelClass($this->_db->fetchAll($select));
    }

    /**
     * @param $driverId
     * @param $statuses
     * @return mixed
     */
    public function cashReturnForDriverId ($driverId, $statuses = ["toreturn"])
    {
        $select = $this->_db->select()
            ->from(
                ["p" => $this->_name],
                [
                    "*",
                    "total" => new \Zend_Db_Expr("SUM(commission_amount)"),
                    "payment_ids" => new \Zend_Db_Expr("GROUP_CONCAT(payment_id)"),
                    "period_from" => new \Zend_Db_Expr("MIN(p.created_at)"),
                    "period_to" => new \Zend_Db_Expr("MAX(p.created_at)")
                ]
            )
            ->joinInner(
                ["d" => "cabride_driver"],
                "d.driver_id = p.driver_id",
                []
            )
            ->joinInner(
                ["c" => "customer"],
                "c.customer_id = d.customer_id",
                [
                    "firstname",
                    "lastname",
                    "email",
                ]
            )
            ->where("p.driver_id = ?", $driverId)
            ->where("p.return_status IN (?)", $statuses)
            ->where("p.method = ?", "cash")
            ->having("total > 0") // Total must greater than 0!
            ->group("driver_id")
        ;

        return $this->_db->fetchRow($select);
    }

    /**
     * @param $valueId
     * @return mixed
     */
    public function aggregatePayout ($valueId)
    {
        $select = $this->_db->select()
            ->from(
                ["p" => $this->_name],
                [
                    "*",
                    "total" => new \Zend_Db_Expr("SUM(amount) - SUM(commission_amount)"),
                    "payment_ids" => new \Zend_Db_Expr("GROUP_CONCAT(payment_id)"),
                    "period_from" => new \Zend_Db_Expr("MIN(p.created_at)"),
                    "period_to" => new \Zend_Db_Expr("MAX(p.created_at)")
                ]
            )
            ->joinInner(
                ["d" => "cabride_driver"],
                "d.driver_id = p.driver_id",
                []
            )
            ->joinInner(
                ["c" => "customer"],
                "c.customer_id = d.customer_id",
                [
                    "firstname",
                    "lastname",
                    "email",
                ]
            )
            ->where("p.value_id = ?", $valueId)
            ->where("p.payout_status = ?", "unpaid")
            ->where("p.method = ?", "credit-card")
            ->having("total > 0") // Total must greater than 0!
            ->group("driver_id")
        ;

        return $this->toModelClass($this->_db->fetchAll($select));
    }

    /**
     * @param $driverId
     * @param $statuses
     * @return mixed
     */
    public function payoutForDriverId ($driverId, $statuses = ["unpaid"])
    {
        $select = $this->_db->select()
            ->from(
                ["p" => $this->_name],
                [
                    "*",
                    "total" => new \Zend_Db_Expr("SUM(amount) - SUM(commission_amount)"),
                    "payment_ids" => new \Zend_Db_Expr("GROUP_CONCAT(payment_id)"),
                    "period_from" => new \Zend_Db_Expr("MIN(p.created_at)"),
                    "period_to" => new \Zend_Db_Expr("MAX(p.created_at)")
                ]
            )
            ->joinInner(
                ["d" => "cabride_driver"],
                "d.driver_id = p.driver_id",
                []
            )
            ->joinInner(
                ["c" => "customer"],
                "c.customer_id = d.customer_id",
                [
                    "firstname",
                    "lastname",
                    "email",
                ]
            )
            ->where("p.driver_id = ?", $driverId)
            ->where("p.payout_status IN (?)", $statuses)
            ->where("p.method = ?", "credit-card")
            ->having("total > 0") // Total must greater than 0!
            ->group("driver_id")
        ;

        return $this->_db->fetchRow($select);
    }

    /**
     * @param $valueId
     * @return mixed
     */
    public function aggregateDashboard ($valueId)
    {
        $oneDay = 86400;

        $now = time();
        $lastWeek = $now - ($oneDay * 7);
        $lastThirtyDays = $now - ($oneDay * 30);
        $lastYear = $now - ($oneDay * 365);

        $todayLow = date("Y-m-d 00:00:00", $now);
        $todayHigh = date("Y-m-d 23:59:59", $now);

        $weekLow = date("Y-m-d 00:00:00", $lastWeek);
        $weekHigh = date("Y-m-d 23:59:59", $now);

        $monthLow = date("Y-m-d 00:00:00", $lastThirtyDays);
        $monthHigh = date("Y-m-d 23:59:59", $now);

        $yearLow = date("Y-m-d 00:00:00", $lastYear);
        $yearHigh = date("Y-m-d 23:59:59", $now);

        // Day!
        $select = $this->_db->select()
            ->from(
                ["p" => $this->_name],
                [
                    "total" => new \Zend_Db_Expr("SUM(amount)"),
                ]
            )
            ->where("p.value_id = ?", $valueId)
            ->where("p.status = ?", "paid")
            ->where("p.created_at >= ?", $todayLow)
            ->where("p.created_at < ? ", $todayHigh);

        $dayRow = $this->_db->fetchOne($select);

        // Week!
        $select = $this->_db->select()
            ->from(
                ["p" => $this->_name],
                [
                    "total" => new \Zend_Db_Expr("SUM(amount)"),
                ]
            )
            ->where("p.value_id = ?", $valueId)
            ->where("p.status = ?", "paid")
            ->where("p.created_at >= ?", $weekLow)
            ->where("p.created_at < ? ", $weekHigh);

        $weekRow = $this->_db->fetchOne($select);

        // Month!
        $select = $this->_db->select()
            ->from(
                ["p" => $this->_name],
                [
                    "total" => new \Zend_Db_Expr("SUM(amount)"),
                ]
            )
            ->where("p.value_id = ?", $valueId)
            ->where("p.status = ?", "paid")
            ->where("p.created_at >= ?", $monthLow)
            ->where("p.created_at < ? ", $monthHigh);

        $monthRow = $this->_db->fetchOne($select);

        // Year!
        $select = $this->_db->select()
            ->from(
                ["p" => $this->_name],
                [
                    "total" => new \Zend_Db_Expr("SUM(amount)"),
                ]
            )
            ->where("p.value_id = ?", $valueId)
            ->where("p.status = ?", "paid")
            ->where("p.created_at >= ?", $yearLow)
            ->where("p.created_at < ? ", $yearHigh);

        $yearRow = $this->_db->fetchOne($select);

        return [
            "dayRow" => $dayRow,
            "weekRow" => $weekRow,
            "monthRow" => $monthRow,
            "yearRow" => $yearRow,
        ];
    }


}
