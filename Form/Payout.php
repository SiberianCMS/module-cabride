<?php

namespace Cabride\Form;

use Siberian_Form_Abstract;

/**
 * Class Payout
 * @package Cabride\Form
 */
class Payout extends Siberian_Form_Abstract
{
    /**
     * @throws \Zend_Form_Exception
     */
    public function init()
    {
        parent::init();

        $this
            ->setAction(__path("/cabride/payout/editpost"))
            ->setAttrib("id", "form-cabride-payout")
            ->addNav("nav-cabride-payout");

        // Bind as a create form!
        self::addClass("create", $this);

        $payout_id = $this->addSimpleHidden("payout_id");

        // Builds the default form from schema!
        
        $driver_id = $this->addSimpleText("driver_id", __("Driver Id"));
        $driver_id->setRequired(true);

        $amount = $this->addSimpleText("amount", __("Amount"));
        $amount->setRequired(true);

        $method = $this->addSimpleText("method", __("Method"));
        $method->setRequired(true);

        $status = $this->addSimpleText("status", __("Status"));
        $status->setRequired(true);#
    }

    /**
     * @param $payout_id
     */
    public function setPayoutId($payout_id)
    {
        $this->getElement("payout_id")->setValue($payout_id)->setRequired(true);
    }
}
