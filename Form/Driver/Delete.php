<?php

namespace Cabride\Form\Driver;

use Siberian_Form_Abstract;
use Zend_Db_Table;

/**
 * Class Delete
 * @package Cabride\Form\Driver
 */
class Delete extends Siberian_Form_Abstract
{
    /**
     * @throws \Zend_Form_Exception
     */
    public function init()
    {
        parent::init();

        $this
            ->setAction(__path("/cabride/driver/deletepost"))
            ->setAttrib("id", "form-delete-cabride-driver")
            ->setConfirmText("You are about to remove this Driver ! Are you sure ?");

        /** Bind as a delete form */
        self::addClass("delete", $this);

        $db = Zend_Db_Table::getDefaultAdapter();
        $select = $db->select()
            ->from('cabride_driver')
            ->where('cabride_driver.driver_id = :value');

        $driver_id = $this->addSimpleHidden("driver_id", p__("cabride", "Driver"));
        $driver_id->addValidator("Db_RecordExists", true, $select);
        $driver_id->setMinimalDecorator();

        $miniSubmit = $this->addMiniSubmit();
    }
}
