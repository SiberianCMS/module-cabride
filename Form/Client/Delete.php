<?php

namespace Cabride\Form\Client;

use Siberian_Form_Abstract;
use Zend_Db_Table;

/**
 * Class Delete
 * @package Cabride\Form\Client
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
            ->setAction(__path("/cabride/client/deletepost"))
            ->setAttrib("id", "form-delete-cabride-client")
            ->setConfirmText("You are about to remove this Client ! Are you sure ?");

        /** Bind as a delete form */
        self::addClass("delete", $this);

        $db = Zend_Db_Table::getDefaultAdapter();
        $select = $db->select()
            ->from('cabride_client')
            ->where('cabride_client.client_id = :value');

        $client_id = $this->addSimpleHidden("client_id", p__("cabride", "Client"));
        $client_id->addValidator("Db_RecordExists", true, $select);
        $client_id->setMinimalDecorator();

        $miniSubmit = $this->addMiniSubmit();
    }
}
