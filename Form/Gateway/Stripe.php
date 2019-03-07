<?php

namespace Cabride\Form\Gateway;

use Siberian_Form_Abstract;

/**
 * Class Stripe
 * @package Cabride\Form\Gateway
 */
class Stripe extends Siberian_Form_Abstract
{
    /**
     * @throws \Zend_Form_Exception
     */
    public function init()
    {
        parent::init();

        $this
            ->setAction(__path("/cabride/gateway/editpost"))
            ->setAttrib("id", "form-gateway-stripe");

        // Bind as a create form!
        self::addClass("create", $this);

        // Builds the default form from schema!
        $this->addSimpleHidden("value_id");
        $gateway = $this->addSimpleHidden("gateway");
        $gateway->setValue("stripe");

        $publicKey = $this->addSimpleText("stripe_public_key", p__("cabride", "Public key"));
        $secretKey = $this->addSimpleText("stripe_secret_key", p__("cabride", "Secret key"));
        //$isSandbox = $this->addSimpleCheckbox("stripe_is_sandbox", p__("cabride", "Sandbox / Testing mode"));

        $publicKey->setRequired(true);
        $secretKey->setRequired(true);

        $save = $this->addSubmit(p__("cabride", "Save"), p__("cabride", "Save"));
        $save->addClass("pull-right");
    }
}
