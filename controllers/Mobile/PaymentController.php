<?php

use Cabride\Model\Cabride;
use Cabride\Model\Client;
use Cabride\Model\ClientVault;
use Cabride\Model\Payment\Avangate\Customer as PaymentCustomer;
use Cabride\Model\Payment\Avangate\Order as PaymentOrder;
use Cabride\Model\Payment\Twocheckout\Charge as PaymentCharge;
use Customer_Model_Customer as Customer;
use Siberian\Exception;
use Siberian\Json;

/**
 * Class Cabride_Mobile_PaymentController
 */
class Cabride_Mobile_PaymentController extends Application_Controller_Mobile_Default
{
    /**
     * @throws Exception
     * @throws Zend_Exception
     */
    public function saveCardAction()
    {
        $optionValue = $this->getCurrentOptionValue();
        $cabride = (new Cabride())->find($optionValue->getId(), "value_id");

        switch ($cabride->getPaymentProvider()) {
            case "stripe":
                $this->saveCardStripe();
                break;
            case "twocheckout":
                $this->saveCardTwocheckout();
                break;
            case "braintree":
                $this->saveCardBraintree();
                break;
        }
    }

    public function saveCardStripe()
    {
        try {
            $application = $this->getApplication();
            $request = $this->getRequest();
            $data = $request->getBodyParams();
            $optionValue = $this->getCurrentOptionValue();
            $customerId = $this->getSession()->getCustomerId();

            $cabride = (new Cabride())->find($optionValue->getId(), "value_id");
            $client = (new Client())->find($customerId, "customer_id");
            $customer = (new Customer())->find($client->getCustomerId());

            if (!$client->getId()) {
                throw new Exception(p__("cabride",
                    "You session expired!"));
            }

            $type = $data["type"];
            $card = $data["card"];

            if ($type !== "stripe") {
                throw new Exception(p__("cabride",
                    "This payment type is not allowed."));
            }

            \Stripe\Stripe::setApiKey($cabride->getStripeSecretKey());
            if (empty($client->getStripeCustomerToken())) {
                // Creates the Stripe customer first!
                $customer = \Stripe\Customer::create([
                    "email" => $customer->getEmail(),
                    "metadata" => [
                        "customer_id" => $customer->getId(),
                        "client_id" => $client->getId(),
                        "value_id" => $optionValue->getId(),
                        "app_id" => $application->getId(),
                    ],
                ]);

                $client
                    ->setStripeCustomerToken($customer["id"])
                    ->save();
            }

            $stripeCustomer = \Stripe\Customer::retrieve($client->getStripeCustomerToken());

            // Attach the card to the customer!
            $stripeCard = $stripeCustomer->sources->create(["source" => $card["id"]]);

            // Search for a similar card!
            $similarVaults = (new ClientVault())->findAll([
                "exp = ?" => $card["card"]["exp_month"] . "/" . substr($card["card"]["exp_year"], 2),
                "last = ?" => $card["card"]["last4"],
                "brand LIKE ?" => $card["card"]["brand"],
                "client_id = ?" => $client->getId(),
                "payment_provider = ?" => $type,
            ]);

            if ($similarVaults->count() > 0) {
                throw new Exception(p__(
                    "cabride",
                    "Seems you already added this card! If there was an error please remove the existing card first, then add it again."));
            }

            $vault = new ClientVault();
            $vault
                ->setClientId($client->getId())
                ->setType("credit-card")
                ->setPaymentProvider("stripe")
                ->setBrand($card["card"]["brand"])
                ->setExp($card["card"]["exp_month"] . "/" . substr($card["card"]["exp_year"], 2))
                ->setLast($card["card"]["last4"])
                ->setCardToken($stripeCard["id"])
                ->setRawPayload(Json::encode($card))
                ->save();

            $clientVaults = (new ClientVault())->fetchForClientId($client->getId());
            $vaults = [];
            foreach ($clientVaults as $clientVault) {
                // Filter vaults by type!
                $data = $clientVault->toJson();
                if ($clientVault->getPaymentProvider() === $type) {
                    $vaults[] = $data;
                }
            }

            $payload = [
                "success" => true,
                "vaults" => $vaults
            ];
        } catch (\Exception $e) {
            $payload = [
                "error" => true,
                "message" => $e->getMessage()
            ];
        }

        $this->_sendJson($payload);
    }

    public function saveCardTwocheckout()
    {
        try {
            $application = $this->getApplication();
            $request = $this->getRequest();
            $data = $request->getBodyParams();
            $optionValue = $this->getCurrentOptionValue();
            $customerId = $this->getSession()->getCustomerId();

            $cabride = (new Cabride())->find($optionValue->getId(), "value_id");
            $client = (new Client())->find($customerId, "customer_id");
            $customer = (new Customer())->find($client->getCustomerId());

            if (!$client->getId()) {
                throw new Exception(p__("cabride", "You session expired!"));
            }

            $type = $data["type"];
            $card = $data["card"];

            if ($type !== "twocheckout") {
                throw new Exception(p__("cabride",
                    "This payment type is not allowed."));
            }

            //$paymentCustomer = new PaymentCustomer(
            //    $cabride->getCheckoutMerchantCode(),
            //    $cabride->getCheckoutSecret());
//
//
            //if (empty($client->getTwocheckoutCustomerToken())) {
            //    $clientPayload = $paymentCustomer->clientToPayload($client);
            //    $checkoutCustomer = $paymentCustomer->createCustomer($clientPayload);
//
            //    $client
            //        ->setTwocheckoutCustomerToken($checkoutCustomer)
            //        ->save();
            //}
            

            //$checkoutCustomer = $paymentCustomer->retrieveCustomer($client->getTwocheckoutCustomerToken());

            //$paymentOrder = new PaymentOrder(
            //    $cabride->getCheckoutMerchantCode(),
            //    $cabride->getCheckoutSecret());

            $paymentCharge = new PaymentCharge(
                $cabride->getCheckoutMerchantCode(),
                $cabride->getCheckoutPrivateKey(),
                (boolean) $cabride->getCheckoutIsSandbox());

            $charge = $paymentCharge->createSale($card["response"]["token"]["token"]);

            echo "<pre>";
            print_r($charge);
            die;

            /**$orderPayload = $paymentOrder->clientAndCardToOrder(
                $client,
                $card,
                $application->getKey(),
                (boolean) $cabride->getCheckoutIsSandbox());
            $creditCard = $paymentOrder->saveCreditCard($orderPayload);*/

            // Search for a similar card!
            $similarVaults = (new ClientVault())->findAll([
                "exp = ?" => $card["expiryMonth"] . "/" . $card["expiryYear"],
                "last = ?" => $creditCard["PaymentDetails"]["PaymentMethod"]["LastDigits"],
                "brand LIKE ?" => $card["cardType"],
                "client_id = ?" => $client->getId(),
                "payment_provider = ?" => $type,
            ]);

            if ($similarVaults->count() > 0) {
                throw new Exception(p__(
                    "cabride",
                    "Seems you already added this card! If there was an error please remove the existing card first, then add it again."));
            }

            $vault = new ClientVault();
            $vault
                ->setClientId($client->getId())
                ->setType("credit-card")
                ->setPaymentProvider("twocheckout")
                ->setBrand($card["cardType"])
                ->setExp($card["expiryMonth"] . "/" . $card["expiryYear"])
                ->setLast($creditCard["PaymentDetails"]["PaymentMethod"]["LastDigits"])
                ->setCardToken($creditCard["RefNo"])
                ->setRawPayload(Json::encode($creditCard))
                ->save();

            $clientVaults = (new ClientVault())->fetchForClientId($client->getId());
            $vaults = [];
            foreach ($clientVaults as $clientVault) {
                // Filter vaults by type!
                $data = $clientVault->toJson();
                if ($clientVault->getPaymentProvider() === $type) {
                    $vaults[] = $data;
                }
            }

            $payload = [
                "success" => true,
                "vaults" => $vaults
            ];
        } catch (\Exception $e) {
            $payload = [
                "error" => true,
                "message" => $e->getMessage()
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     * @throws Exception
     */
    public function saveCardBraintree()
    {
        // @todo
        throw new Exception(p__("cabride", "Braintree is not available."));
    }
}
