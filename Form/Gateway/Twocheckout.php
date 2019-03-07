<?php

namespace Cabride\Form\Gateway;

use Siberian_Form_Abstract;

/**
 * Class Twocheckout
 * @package Cabride\Form\Gateway
 */
class Twocheckout extends Siberian_Form_Abstract
{
    /**
     * @throws \Zend_Form_Exception
     */
    public function init()
    {
        parent::init();

        $this
            ->setAction(__path("/cabride/gateway/editpost"))
            ->setAttrib("id", "form-gateway-twocheckout");

        // Bind as a create form!
        self::addClass("create", $this);

        // Builds the default form from schema!
        $this->addSimpleHidden("value_id");
        $gateway = $this->addSimpleHidden("gateway");
        $gateway->setValue("twocheckout");

        $merchantCode = $this->addSimpleText("checkout_merchant_code", p__("cabride", "Merchant code"));
        $secretKey = $this->addSimpleText("checkout_secret", p__("cabride", "Secret key"));
        //$username = $this->addSimpleText("checkout_username", p__("cabride", "Username"));
        //$password = $this->addSimpleText("checkout_password", p__("cabride", "Password"));
        $isSandbox = $this->addSimpleCheckbox("checkout_is_sandbox", p__("cabride", "Sandbox mode"));

        $merchantCode->setRequired(true);
        $secretKey->setRequired(true);
        //$username->setRequired(true);
        //$password->setRequired(true);

        $save = $this->addSubmit(p__("cabride", "Save"), p__("cabride", "Save"));
        $save->addClass("pull-right");
    }
}
