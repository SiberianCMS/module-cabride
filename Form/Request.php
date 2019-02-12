<?php

namespace Cabride\Form;

use Siberian_Form_Abstract;

/**
 * Class Request
 * @package Cabride\Form
 */
class Request extends Siberian_Form_Abstract
{
    /**
     * @throws \Zend_Form_Exception
     */
    public function init()
    {
        parent::init();

        $this
            ->setAction(__path("/cabride/request/editpost"))
            ->setAttrib("id", "form-cabride-request")
            ->addNav("nav-cabride-request");

        // Bind as a create form!
        self::addClass("create", $this);

        $request_id = $this->addSimpleHidden("request_id");

        // Builds the default form from schema!
        
        $client_id = $this->addSimpleText("client_id", __("Client Id"));
        $client_id->setRequired(true);

        $status = $this->addSimpleText("status", __("Status"));
        $status->setRequired(true);

        $cost = $this->addSimpleText("cost", __("Cost"));
        $cost->setRequired(true);

        $from_address = $this->addSimpleTextarea("from_address", __("From Address"));
        $from_address->setRequired(true);

        $from_lat = $this->addSimpleText("from_lat", __("From Lat"));
        $from_lat->setRequired(true);

        $from_lng = $this->addSimpleText("from_lng", __("From Lng"));
        $from_lng->setRequired(true);

        $to_address = $this->addSimpleTextarea("to_address", __("To Address"));
        $to_address->setRequired(true);

        $to_lat = $this->addSimpleText("to_lat", __("To Lat"));
        $to_lat->setRequired(true);

        $to_lng = $this->addSimpleText("to_lng", __("To Lng"));
        $to_lng->setRequired(true);

        $request_mode = $this->addSimpleText("request_mode", __("Request Mode"));
        $request_mode->setRequired(true);#
    }

    /**
     * @param $request_id
     */
    public function setRequestId($request_id)
    {
        $this->getElement("request_id")->setValue($request_id)->setRequired(true);
    }
}
