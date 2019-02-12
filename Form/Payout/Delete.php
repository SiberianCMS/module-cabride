<?php

namespace Cabride\Form\Payout;

use Siberian_Form_Abstract;
use Zend_Db_Table;

/**
 * Class Delete
 * @package Cabride\Form\Payout
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
            ->setAction(__path("/cabride/payout/deletepost"))
            ->setAttrib("id", "form-delete-cabride-payout")
            ->setConfirmText("You are about to remove this Payout ! Are you sure ?");

        /** Bind as a delete form */
        self::addClass("delete", $this);

        $db = Zend_Db_Table::getDefaultAdapter();
        $select = $db->select()
            ->from('cabride_payout')
            ->where('cabride_payout.payout_id = :value');

        $payout_id = $this->addSimpleHidden("payout_id", __("Payout"));
        $payout_id->addValidator("Db_RecordExists", true, $select);
        $payout_id->setMinimalDecorator();

        $miniSubmit = $this->addMiniSubmit();
    }
}
