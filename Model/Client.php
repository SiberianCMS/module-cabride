<?php

namespace Cabride\Model;

use Core\Model\Base;

/**
 * Class Client
 * @package Cabride\Model
 *
 * @method Db\Table\Client getTable()
 */
class Client extends Base
{
    /**
     * Client constructor.
     * @param array $params
     * @throws \Zend_Exception
     */
    public function __construct($params = [])
    {
        parent::__construct($params);
        $this->_db_table = 'Cabride\Model\Db\Table\Client';
        return $this;
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