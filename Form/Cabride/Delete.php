<?php

/**
 * Class Cabride_Form_Cabride_Delete
 */
class Cabride_Form_Cabride_Delete extends Siberian_Form_Abstract
{
    /**
     * init wrapper
     */
    public function init()
    {
        parent::init();

        $this
            ->setAction(__path("/cabride/cabride/deletepost"))
            ->setAttrib("id", "form-delete-cabride")
            ->setConfirmText("You are about to remove this Cabride ! Are you sure ?");

        /** Bind as a delete form */
        self::addClass("delete", $this);

        $db = Zend_Db_Table::getDefaultAdapter();
        $select = $db->select()
            ->from('cabride')
            ->where('cabride.cabride_id = :value');

        $cabride_id = $this->addSimpleHidden("cabride_id", __("Cabride"));
        $cabride_id->addValidator("Db_RecordExists", true, $select);
        $cabride_id->setMinimalDecorator();

        $miniSubmit = $this->addMiniSubmit();
    }
}
