<?php

namespace Cabride\Model\Db\Table;

use Core_Model_Db_Table;

/**
 * Class Cashreturn
 * @package Cabride\Model\Db\Table
 */
class Cashreturn extends Core_Model_Db_Table
{
    /**
     * @var string
     */
    protected $_name = "cabride_cashreturn";

    /**
     * @var string
     */
    protected $_primary = "cashreturn_id";

    /**
     * @param $valueId
     * @return mixed
     * @throws \Zend_Exception
     */
    public function fetchArchives ($valueId)
    {
        $select = $this->_db->select()
            ->from(
                ["cr" => $this->_name],
                [
                    "*",
                ]
            )
            ->joinInner(
                ["d" => "cabride_driver"],
                "d.driver_id = cr.driver_id",
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
            ->where("cr.value_id = ?", $valueId)
            ->order("FIELD(cr.status, 'requested','returned'), cr.cashreturn_id DESC")
        ;

        return $this->toModelClass($this->_db->fetchAll($select));
    }
}
