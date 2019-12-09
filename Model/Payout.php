<?php

namespace Cabride\Model;

use Core\Model\Base;

/**
 * Class Payout
 * @package Cabride\Model
 *
 * @method integer getId()
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
        $this->_db_table = Db\Table\Payout::class;
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