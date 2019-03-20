<?php

namespace Cabride\Model\Db\Table;

use Core_Model_Db_Table;

/**
 * Class PayoutBulk
 * @package Cabride\Model\Db\Table
 */
class PayoutBulk extends Core_Model_Db_Table
{
    /**
     * @var string
     */
    protected $_name = "cabride_payout_bulk";

    /**
     * @var string
     */
    protected $_primary = "bulk_id";

    /**
     * @param $valueId
     * @return mixed
     */
    public function fetchArchives ($valueId)
    {
        $select = $this->_db->select()
            ->from(
                ["pb" => $this->_name],
                [
                    "*",
                ]
            )
            ->where("pb.value_id = ?", $valueId)
            ->order("pb.bulk_id DESC")
        ;

        return $this->toModelClass($this->_db->fetchAll($select));
    }
}
