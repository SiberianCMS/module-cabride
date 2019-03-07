<?php

use Cabride\Model\Cabride;
use Cabride\Model\Client;
use Cabride\Model\ClientVault;
use Customer_Model_Customer as Customer;
use Siberian\Exception;
use Siberian\Json;

/**
 * Class Cabride_Mobile_PaymentController
 */
class Cabride_Mobile_PaymentController extends Application_Controller_Mobile_Default
{
    /**
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
}
