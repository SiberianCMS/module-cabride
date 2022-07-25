<?php

namespace Cabride\Form;

use Siberian_Form_Abstract;

/**
 * Class Payment
 * @package Cabride\Form
 */
class Payment extends Siberian_Form_Abstract
{
    /**
     * @throws \Zend_Form_Exception
     */
    public function init()
    {
        parent::init();

        $this
            ->setAction(__path("/cabride/payment/editpost"))
            ->setAttrib("id", "form-cabride-payment")
            ->addNav("nav-cabride-payment");

        // Bind as a create form!
        self::addClass("create", $this);

        $payment_id = $this->addSimpleHidden("payment_id");

        // Builds the default form from schema!
        
        $amount = $this->addSimpleText("amount", p__('cabride', "Amount"));
        $amount->setRequired(true);

        $method = $this->addSimpleText("method", p__('cabride', "Method"));
        $method->setRequired(true);

        $status = $this->addSimpleText("status", p__('cabride', "Status"));
        $status->setRequired(true);#
    }

    /**
     * @param $payment_id
     */
    public function setPaymentId($payment_id)
    {
        $this->getElement("payment_id")->setValue($payment_id)->setRequired(true);
    }
}
