<?php

/**
 * Class Cabride_Form_Vehicle_Delete
 */
class Cabride_Form_Vehicle_Delete extends Siberian_Form_Abstract
{
    /**
     * init wrapper
     */
    public function init()
    {
        parent::init();

        $this
            ->setAction(__path("/cabride/vehicle/deletepost"))
            ->setAttrib("id", "form-delete-cabride-vehicle")
            ->setConfirmText("You are about to remove this Vehicle ! Are you sure ?");

        /** Bind as a delete form */
        self::addClass("delete", $this);

        $db = Zend_Db_Table::getDefaultAdapter();
        $select = $db->select()
            ->from('cabride_vehicle')
            ->where('cabride_vehicle.vehicle_id = :value');

        $vehicle_id = $this->addSimpleHidden("vehicle_id", __("Vehicle"));
        $vehicle_id->addValidator("Db_RecordExists", true, $select);
        $vehicle_id->setMinimalDecorator();

        $miniSubmit = $this->addMiniSubmit();
    }
}
