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

    /**
     * @param $clientId
     * @return mixed
     */
    public function hasInProgressRequest($clientId)
    {
        $select = $this->_db
            ->select()
            ->from('cabride_request', ['COUNT(*) AS total'])
            ->where('client_id = ?', $clientId)
            ->where('status IN (?)', ['pending', 'accepted', 'onway']);

        return $this->_db->fetchRow($select);
    }
}
