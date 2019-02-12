<?php

namespace Cabride\Form\Gateway;

use Siberian_Form_Abstract;

/**
 * Class Braintree
 * @package Cabride\Form\Gateway
 */
class Braintree extends Siberian_Form_Abstract
{
    /**
     * @throws \Zend_Form_Exception
     */
    public function init()
    {
        parent::init();

        $this
            ->setAction(__path("/cabride/gateway/editpost"))
            ->setAttrib("id", "form-gateway-braintree");

        // Bind as a create form!
        self::addClass("create", $this);

        // Builds the default form from schema!
        $this->addSimpleHidden("value_id");
        $gateway = $this->addSimpleHidden("gateway");
        $gateway->setValue("braintree");

        $publicKey = $this->addSimpleText("braintree_merchant_id", p__("cabride", "Merchant ID"));
        $publicKey = $this->addSimpleText("braintree_public_key", p__("cabride", "Public key"));
        $secretKey = $this->addSimpleText("braintree_private_key", p__("cabride", "Private key"));
        $isSandbox = $this->addSimpleCheckbox("braintree_is_sandbox", p__("cabride", "Sandbox mode"));

        $publicKey->setRequired(true);
        $secretKey->setRequired(true);

        $save = $this->addSubmit(p__("cabride", "Save"), p__("cabride", "Save"));
        $save->addClass("pull-right");
    }
}
