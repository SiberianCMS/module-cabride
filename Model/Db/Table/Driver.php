<?php

namespace Cabride\Model\Db\Table;

use Core_Model_Db_Table;

/**
 * Class Driver
 * @package Cabride\Model\Db\Table
 */
class Driver extends Core_Model_Db_Table
{
    /**
     * @var string
     */
    protected $_name = "cabride_driver";

    /**
     * @var string
     */
    protected $_primary = "driver_id";

    /**
     * @param $valueId
     * @param $formula
     * @return mixed
     */
    public function findNearestOnline($valueId, $formula)
    {
        $select = $this->_db->select()
            ->from($this->_name, [
                "*",
                "distance" => $formula
            ])
            ->where("value_id = ?", $valueId)
            ->where("(latitude != 0 AND longitude != 0)")
            //->having("distance < 10000")
        ;

        return $this->toModelClass($this->_db->fetchAll($select));
    }


}
