<?php

namespace Cabride\Model;

use Core\Model\Base;

/**
 * Class RequestLog
 * @package Cabride\Model
 *
 * @method integer getId()
 */
class RequestLog extends Base
{
    /**
     * Request constructor.
     * @param array $params
     * @throws \Zend_Exception
     */
    public function __construct($params = [])
    {
        parent::__construct($params);
        $this->_db_table = Db\Table\RequestLog::class;
    }
}