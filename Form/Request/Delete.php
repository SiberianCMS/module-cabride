<?php

namespace Cabride\Form\Request;

use Siberian_Form_Abstract;
use Zend_Db_Table;

/**
 * Class Delete
 * @package Cabride\Form\Request
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
            ->setAction(__path("/cabride/request/deletepost"))
            ->setAttrib("id", "form-delete-cabride-request")
            ->setConfirmText("You are about to remove this Request ! Are you sure ?");

        /** Bind as a delete form */
        self::addClass("delete", $this);

        $db = Zend_Db_Table::getDefaultAdapter();
        $select = $db->select()
            ->from('cabride_request')
            ->where('cabride_request.request_id = :value');

        $request_id = $this->addSimpleHidden("request_id", p__("cabride", "Request"));
        $request_id->addValidator("Db_RecordExists", true, $select);
        $request_id->setMinimalDecorator();

        $miniSubmit = $this->addMiniSubmit();
    }
}
