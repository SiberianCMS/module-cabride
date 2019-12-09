<?php

namespace Cabride\Model;

use Core\Model\Base;

/**
 * Class PayoutBulk
 * @package Cabride\Model
 *
 * @method integer getId()
 */
class PayoutBulk extends Base
{
    /**
     * Payout constructor.
     * @param array $params
     * @throws \Zend_Exception
     */
    public function __construct($params = [])
    {
        parent::__construct($params);
        $this->_db_table = Db\Table\PayoutBulk::class;
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