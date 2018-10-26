<?php

/**
 * Class Cabride_Form_Client
 */
class Cabride_Form_Client extends Siberian_Form_Abstract
{
    /**
     * init wrapper
     */
    public function init()
    {
        parent::init();

        $this
            ->setAction(__path("/cabride/client/editpost"))
            ->setAttrib("id", "form-cabride-client")
            ->addNav("nav-cabride-client");

        // Bind as a create form!
        self::addClass("create", $this);

        $client_id = $this->addSimpleHidden("client_id");

        // Builds the default form from schema!
        
        $customer_id = $this->addSimpleText("customer_id", __("Customer Id"));
        $customer_id->setRequired(true);#
    }

    /**
     * @param $client_id
     */
    public function setClientId($client_id)
    {
        $this->getElement("client_id")->setValue($client_id)->setRequired(true);
    }
}
