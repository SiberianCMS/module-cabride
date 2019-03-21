<?php

namespace Cabride\Model;

use Core\Model\Base;

/**
 * Class Payout
 * @package Cabride\Model
 *
 * @method Db\Table\Payout getTable()
 */
class Payout extends Base
{
    /**
     * Payout constructor.
     * @param array $params
     * @throws \Zend_Exception
     */
    public function __construct($params = [])
    {
        parent::__construct($params);
        $this->_db_table = 'Cabride\Model\Db\Table\Payout';
        return $this;
    }

    /**
     * @param $valueId
     * @return mixed
     */
    public function fetchArchives ($valueId)
    {
        return $this->getTable()->fetchArchives($valueId);
    }
}