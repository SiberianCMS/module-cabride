<?php

/**
 * Class Cabride_Form_Payment
 */
class Cabride_Form_Payment extends Siberian_Form_Abstract
{
    /**
     * init wrapper
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
        
        $amount = $this->addSimpleText("amount", __("Amount"));
        $amount->setRequired(true);

        $method = $this->addSimpleText("method", __("Method"));
        $method->setRequired(true);

        $status = $this->addSimpleText("status", __("Status"));
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
