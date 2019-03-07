<?php

use Cabride\Form\Gateway\Stripe;
use Cabride\Model\Cabride;
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

            $values["stripe_is_sandbox"] = 0;
            if (preg_match("/_test_/i", $values["stripe_public_key"]) === 1) {
                $values["stripe_is_sandbox"] = 1;
            }

            $cabride
                ->addData($values)
                ->setPaymentProvider("stripe") // Enforce stripe for now!
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

}