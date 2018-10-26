<?php

/**
 * Class Cabride_Form_Payment_Delete
 */
class Cabride_Form_Payment_Delete extends Siberian_Form_Abstract
{
    /**
     * init wrapper
     */
    public function init()
    {
        parent::init();

        $this
            ->setAction(__path("/cabride/payment/deletepost"))
            ->setAttrib("id", "form-delete-cabride-payment")
            ->setConfirmText("You are about to remove this Payment ! Are you sure ?");

        /** Bind as a delete form */
        self::addClass("delete", $this);

        $db = Zend_Db_Table::getDefaultAdapter();
        $select = $db->select()
            ->from('cabride_payment')
            ->where('cabride_payment.payment_id = :value');

        $payment_id = $this->addSimpleHidden("payment_id", __("Payment"));
        $payment_id->addValidator("Db_RecordExists", true, $select);
        $payment_id->setMinimalDecorator();

        $miniSubmit = $this->addMiniSubmit();
    }
}
