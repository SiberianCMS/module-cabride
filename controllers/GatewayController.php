<?php

use Cabride\Form\Gateway\Stripe;
use Cabride\Form\Gateway\Twocheckout;
use Cabride\Form\Gateway\Braintree;
use Cabride\Model\Cabride;
use Cabride\Model\Payment\Avangate\Payouts;
use Siberian\Exception;

/**
 * Class Cabride_ApplicationController
 */
class Cabride_GatewayController extends Application_Controller_Default
{
    /**
     *
     */
    public function editpostAction()
    {
        $form = false;
        try {
            $values = $this->getRequest()->getPost();

            switch ($values["gateway"]) {
                case "stripe":
                    self::testStripe($values);
                    $form = new Stripe();
                    break;
                case "twocheckout":
                    self::testTwocheckout($values);
                    $form = new Twocheckout();
                    break;
                case "braintree":
                    self::testBraintree($values);
                    $form = new Braintree();
                    break;
            }

            if ($form === false) {
                throw new Exception(__("Invalid gateway"));
            }

            if (!$form->isValid($values)) {
                throw new Exception(__("Invalid form"));
            }

            /** Do whatever you need when form is valid */
            $cabride = (new Cabride())
                ->find($values["value_id"], "value_id");

            unset($values["value_id"]);

            $cabride
                ->addData($values)
                ->save();

            $payload = [
                'success' => true,
                'message' => __('Success'),
            ];
        } catch (\Exception $e) {
            if ($form === false) {
                $payload = [
                    'error' => true,
                    'message' => $e->getMessage(),
                ];
            } else {
                $payload = [
                    'error' => true,
                    'message' => $form->getTextErrors(),
                    'errors' => $form->getTextErrors(true),
                ];
            }
        }

        $this->_sendJson($payload);
    }

    /**
     * @param $values
     * @throws \Siberian\Exception
     */
    private static function testStripe($values)
    {
        try {
            \Stripe\Stripe::setApiKey($values["stripe_secret_key"]);
            \Stripe\Customer::all();
        } catch (\Exception $e) {
            throw new Exception(__("Stripe API Error: %s", $e->getMessage()));
        }
    }

    /**
     * @param $values
     * @throws \Siberian\Exception
     */
    private static function testBraintree($values)
    {
        $settings = [
            "environment" => $values["braintree_is_sandbox"] ? "sandbox" : "production",
            "merchantId" => $values["braintree_merchant_id"],
            "publicKey" => $values["braintree_public_key"],
            "privateKey" => $values["braintree_private_key"]
        ];

        $gateway = new \Braintree\Gateway($settings);

        try {
            $clientToken = $gateway->clientToken()->generate();
        } catch (\Braintree\Exception $e) {
            // rethrow auth exception
            throw new Exception(__("BrainTree API Error: Invalid credentials"));
        } catch (\Exception $e) {
            throw new Exception(__("BrainTree API Error: Invalid credentials, %s", get_class($e)));
        }
    }

    /**
     * @param $values
     * @throws Exception
     * @throws \Exception
     */
    private static function testTwocheckout($values)
    {
        $test = new Payouts($values["checkout_merchant_code"], $values["checkout_secret"]);

        try {
            $test->getPayouts();
        } catch (\Exception $e) {
            throw new Exception(__("2Checkout API Error: %s", $e->getMessage()));
        }
    }

}