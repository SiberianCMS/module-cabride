<?php

namespace Cabride\Model\Db\Table;

use Core_Model_Db_Table;

/**
 * Class Client
 * @package Cabride\Model\Db\Table
 */
class Client extends Core_Model_Db_Table
{
    /**
     * @var string
     */
    protected $_name = "cabride_client";

    /**
     * @var string
     */
    protected $_primary = "client_id";

    /**
     * @param $valueId
     * @return mixed
     */
    public function fetchForValueId($valueId)
    {
        $select = $this->_db->select()
            ->from(["client" => $this->_name])
            ->joinInner("customer", "client.customer_id = customer.customer_id")
            ->where("client.value_id = ?", $valueId);

        return $this->toModelClass($this->_db->fetchAll($select));
    }
}
