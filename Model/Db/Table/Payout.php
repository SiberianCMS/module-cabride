<?php

namespace Cabride\Model\Db\Table;

use Core_Model_Db_Table;

/**
 * Class Payout
 * @package Cabride\Model\Db\Table
 */
class Payout extends Core_Model_Db_Table
{
    /**
     * @var string
     */
    protected $_name = "cabride_payout";

    /**
     * @var string
     */
    protected $_primary = "payout_id";

    /**
     * @param $valueId
     * @return mixed
     */
    public function fetchArchives ($valueId)
    {
        $select = $this->_db->select()
            ->from(
                ["pr" => $this->_name],
                [
                    "*",
                ]
            )
            ->joinInner(
                ["d" => "cabride_driver"],
                "d.driver_id = pr.driver_id",
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
            ->where("pr.value_id = ?", $valueId)
            ->order("pr.payout_id DESC")
        ;

        return $this->toModelClass($this->_db->fetchAll($select));
    }
}
