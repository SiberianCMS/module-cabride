<?php

/**
 * Class Cabride_ApplicationController
 */
class Cabride_GatewayController extends Application_Controller_Default
{
    /**
     * Create/Edit Payment
     *
     * @throws exception
     */
    public function editpostAction()
    {
        $form = false;
        try {
            $values = $this->getRequest()->getPost();

            switch ($values["gateway"]) {
                case "stripe":
                    self::testStripe($values);
                    $form = new Cabride_Form_Gateway_Stripe();
                    break;
                case "2checkout":
                    self::testTwocheckout($values);
                    $form = new Cabride_Form_Gateway_Twocheckout();
                    break;
                case "braintree":
                    self::testBraintree($values);
                    $form = new Cabride_Form_Gateway_Braintree();
                    break;
            }

            if ($form === false) {
                throw new \Siberian\Exception(__("Invalid gateway"));
            }

            if (!$form->isValid($values)) {
                throw new \Siberian\Exception(__("Invalid form"));
            }

            /** Do whatever you need when form is valid */
            $cabride = (new Cabride_Model_Cabride())
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
            throw new \Siberian\Exception(__("Stripe API Error: %s", $e->getMessage()));
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
            throw new \Siberian\Exception(__("BrainTree API Error: Invalid credentials"));
        } catch (\Exception $e) {
            throw new \Siberian\Exception(__("BrainTree API Error: Invalid credentials, %s", get_class($e)));
        }
    }

    /**
     * @param $values
     * @throws \Siberian\Exception
     */
    private static function testTwocheckout($values)
    {
        \Twocheckout::privateKey($values["checkout_secret"]);
        \Twocheckout::sellerId($values["checkout_sid"]);
        \Twocheckout::sandbox($values["checkout_is_sandbox"] ? true : false);

        try {
            Twocheckout_Charge::auth([
                "sellerId" => $values["checkout_sid"],
                "merchantOrderId" => "123",
                "token" => "MjFiYzIzYjAtYjE4YS00ZmI0LTg4YzYtNDIzMTBlMjc0MDlk",
                "currency" => "USD",
                "total" => "10.00",
                "billingAddr" => [
                    "name" => "Testing Tester",
                    "addrLine1" => "123 Test St",
                    "city" => "Columbus",
                    "state" => "OH",
                    "zipCode" => "43123",
                    "country" => "USA",
                    "email" => "testingtester@2co.com",
                    "phoneNumber" => "555-555-5555"
                ],
                "shippingAddr" => [
                    "name" => "Testing Tester",
                    "addrLine1" => "123 Test St",
                    "city" => "Columbus",
                    "state" => "OH",
                    "zipCode" => "43123",
                    "country" => "USA",
                    "email" => "testingtester@2co.com",
                    "phoneNumber" => "555-555-5555"
                ]
            ]);
        } catch (\Twocheckout_Error $e) {
            throw new \Siberian\Exception(__("2Checkout API Error: %s", $e->getMessage()));
        }
    }

}