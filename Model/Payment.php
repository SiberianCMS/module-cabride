<?php

namespace Cabride\Model;

use Core\Model\Base;

/**
 * Class Payment
 * @package Cabride\Model
 */
class Payment extends Base
{
    /**
     * Payment constructor.
     * @param array $params
     * @throws \Zend_Exception
     */
    public function __construct($params = [])
    {
        parent::__construct($params);
        $this->_db_table = 'Cabride\Model\Db\Table\Payment';
        return $this;
    }
}