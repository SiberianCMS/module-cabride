<?php

namespace Cabride\Model;

use Core\Model\Base;

/**
 * Class Field
 * @package Cabride\Model
 *
 * @method Db\Table\Field getTable()
 */
class Field extends Base
{
    /**
     * Field constructor.
     * @param array $datas
     * @throws \Zend_Exception
     */
    public function __construct($datas = [])
    {
        parent::__construct($datas);
        $this->_db_table = "Cabride\Model\Db\Table\Field";
    }

    /**
     * @param $valueId
     * @return $this
     */
    public function initPosition($valueId)
    {
        $position = $this->getTable()->getLastPosition($valueId);

        return $this->setData("position", $position["position"] + 1);
    }
}
