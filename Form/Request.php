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
        
        $client_id = $this->addSimpleText("client_id", p__("cabride", "Client Id"));
        $client_id->setRequired(true);

        $status = $this->addSimpleText("status", p__("cabride", "Status"));
        $status->setRequired(true);

        $cost = $this->addSimpleText("cost", p__("cabride", "Cost"));
        $cost->setRequired(true);

        $from_address = $this->addSimpleTextarea("from_address", p__("cabride", "From Address"));
        $from_address->setRequired(true);

        $from_lat = $this->addSimpleText("from_lat", p__("cabride", "From Lat"));
        $from_lat->setRequired(true);

        $from_lng = $this->addSimpleText("from_lng", p__("cabride", "From Lng"));
        $from_lng->setRequired(true);

        $to_address = $this->addSimpleTextarea("to_address", p__("cabride", "To Address"));
        $to_address->setRequired(true);

        $to_lat = $this->addSimpleText("to_lat", p__("cabride", "To Lat"));
        $to_lat->setRequired(true);

        $to_lng = $this->addSimpleText("to_lng", p__("cabride", "To Lng"));
        $to_lng->setRequired(true);

        $request_mode = $this->addSimpleText("request_mode", p__("cabride", "Request Mode"));
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
