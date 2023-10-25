<?php

namespace Cabride\Model;

/**
 * Class Client
 * @package Cabride\Model
 *
 * @method integer getId()
 * @method Db\Table\Client getTable()
 */
class Client extends PushCapable
{
    /**
     * Client constructor.
     * @param array $params
     * @throws \Zend_Exception
     */
    public function __construct($params = [])
    {
        parent::__construct($params);
        $this->_db_table = Db\Table\Client::class;
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
     * @return bool
     */
    public function hasInProgressRequest()
    {
        $result = $this->getTable()->hasInProgressRequest($this->getClientId());

        if (array_key_exists("total", $result) && $result["total"] > 0) {
            return true;
        }
        return false;
    }
}