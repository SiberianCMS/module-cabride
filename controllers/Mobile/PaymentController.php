<?php

use Cabride\Model\Cabride;
use Cabride\Model\Client;
use Cabride\Model\ClientVault;
use Cabride\Model\Request;
use Customer_Model_Customer as Customer;
use Siberian\Exception;
use Siberian\Json;
use Cabride\Controller\Mobile as MobileController;

use Stripe\Stripe;
use Stripe\SetupIntent;
use Stripe\PaymentMethod;
use Stripe\Customer as StripeCustomer;
use Stripe\Error\InvalidRequest;

/**
 * Class Cabride_Mobile_PaymentController
 */
class Cabride_Mobile_PaymentController extends MobileController
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

    public function getSetupIntentAction ()
    {
        try {
            $application = $this->getApplication();
            $request = $this->getRequest();
            $optionValue = $this->getCurrentOptionValue();
            $customerId = $this->getSession()->getCustomerId();

            $cabride = (new Cabride())->find($optionValue->getId(), "value_id");
            $client = (new Client())->find($customerId, "customer_id");
            $customer = (new Customer())->find($client->getCustomerId());

            if (!$client->getId()) {
                throw new Exception(p__("cabride",
                    "You session expired!"));
            }

            Stripe::setApiKey($cabride->getStripeSecretKey());

            if (empty($client->getStripeCustomerToken())) {
                // Creates the Stripe customer first!
                $stripeCustomer = StripeCustomer::create([
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
            } else {
                try {
                    $stripeCustomer = StripeCustomer::retrieve($client->getStripeCustomerToken());
                } catch (InvalidRequest $e) {
                    // Seems the customer doesn't exists anymore, or the Stripe account changed!
                    if ($e->getStripeCode() === "resource_missing") {
                        // Creates the Stripe customer first!
                        $stripeCustomer = StripeCustomer::create([
                            "email" => $customer->getEmail(),
                            "metadata" => [
                                "customer_id" => $customer->getId(),
                                "client_id" => $client->getId(),
                                "value_id" => $optionValue->getId(),
                                "app_id" => $application->getId(),
                            ],
                        ]);

                        $client
                            ->archiveStripCustomerToken()
                            ->setStripeCustomerToken($customer["id"])
                            ->save();
                    } else {
                        throw $e;
                    }
                }
            }

            $setupIntent = SetupIntent::create([
                "payment_method_types" => ["card"],
                "customer" => $stripeCustomer["id"]
            ]);

            $payload = [
                "success" => true,
                "setupIntent" => $setupIntent
            ];
        } catch (\Exception $e) {
            $payload = [
                "error" => true,
                "message" => $e->getMessage()
            ];
        }

        $this->_sendJson($payload);
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

            Stripe::setApiKey($cabride->getStripeSecretKey());

            if (empty($client->getStripeCustomerToken())) {
                // Creates the Stripe customer first!
                $stripeCustomer = StripeCustomer::create([
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
            } else {
                try {
                    $stripeCustomer = StripeCustomer::retrieve($client->getStripeCustomerToken());
                } catch (InvalidRequest $e) {
                    // Seems the customer doesn't exists anymore, or the Stripe account changed!
                    if ($e->getStripeCode() === "resource_missing") {
                        // Creates the Stripe customer first!
                        $stripeCustomer = StripeCustomer::create([
                            "email" => $customer->getEmail(),
                            "metadata" => [
                                "customer_id" => $customer->getId(),
                                "client_id" => $client->getId(),
                                "value_id" => $optionValue->getId(),
                                "app_id" => $application->getId(),
                            ],
                        ]);

                        $client
                            ->archiveStripCustomerToken()
                            ->setStripeCustomerToken($customer["id"])
                            ->save();
                    } else {
                        throw $e;
                    }
                }
            }

            // Attach the card (PaymentMethod) to the customer!
            $paymentMethod = PaymentMethod::retrieve($card["setupIntent"]["payment_method"]);
            $paymentMethod->attach(["customer" => $stripeCustomer["id"]]);

            // Search for a similar card!
            $similarVaults = (new ClientVault())->findAll([
                "exp = ?" => $paymentMethod["card"]["exp_month"] . "/" . substr($paymentMethod["card"]["exp_year"], 2),
                "last = ?" => $paymentMethod["card"]["last4"],
                "brand LIKE ?" => $paymentMethod["card"]["brand"],
                "client_id = ?" => $client->getId(),
                "payment_provider = ?" => $type,
                "is_removed = ?" => "0",
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
                ->setBrand($paymentMethod["card"]["brand"])
                ->setExp($paymentMethod["card"]["exp_month"] . "/" . substr($paymentMethod["card"]["exp_year"], 2))
                ->setLast($paymentMethod["card"]["last4"])
                ->setPaymentMethod($paymentMethod["id"])
                ->setRawPayload(Json::encode($paymentMethod))
                ->setIsRemoved(0)
                ->save();

            /**
             * @var $clientVaults ClientVault[]
             */
            $clientVaults = (new ClientVault())->fetchForClientId($client->getId());
            $vaults = [];
            foreach ($clientVaults as $clientVault) {

                // Just in case, we skip "card_xxx" old vaults!
                if (!empty($clientVault->getCardToken())) {
                    continue;
                }

                // Filter vaults by type!
                $data = $clientVault->toJson();
                if ($clientVault->getPaymentProvider() === $type) {
                    $vaults[] = $data;
                }
            }

            $payload = [
                "success" => true,
                "lastVaultId" => $vault->getId(),
                "vaults" => $vaults
            ];
        } catch (\Exception $e) {
            $payload = [
                "error" => true,
                "message" => $e->getMessage(),
                "trace" => $e->getTrace()
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     * @throws Zend_Exception
     */
    public function deleteVaultAction()
    {
        $optionValue = $this->getCurrentOptionValue();
        $cabride = (new Cabride())->find($optionValue->getId(), "value_id");

        switch ($cabride->getPaymentProvider()) {
            case "stripe":
                $this->deleteVaultStripe();
                break;
        }
    }

    public function deleteVaultStripe()
    {
        try {
            $application = $this->getApplication();
            $request = $this->getRequest();
            $vaultId = $request->getParam("vaultId", null);
            $optionValue = $this->getCurrentOptionValue();
            $customerId = $this->getSession()->getCustomerId();

            $client = (new Client())->find($customerId, "customer_id");
            $cabride = (new Cabride())->find($optionValue->getId(), "value_id");
            $vault = (new ClientVault())->find([
                "client_vault_id" => $vaultId,
                "payment_provider" => "stripe",
            ]);

            if (!$vault->getId()) {
                throw new Exception(p__("cabride",
                    "This vault doesn't exists!"));
            }

            // Check if the vault can be safely removed!
            $requests = (new Request())->findAll([
                "client_vault_id = ?" => $vaultId,
                "status IN (?)" => ["pending", "accepted", "onway", "inprogress"]
            ]);

            if ($requests->count() > 0) {
                throw new Exception(p__("cabride",
                    "This vault can't be removed yet, it is currently used for a ride!"));
            }

            Stripe::setApiKey($cabride->getStripeSecretKey());

            // Delete the card from the Stripe customer!
            $paymentMethod = PaymentMethod::retrieve($vault->getPaymentMethod());
            $paymentMethod->detach();

            // "remove" the vault, we keep tack of it for recap pages & stripe search history!
            // We previously used the key `is_deleted`, but there is flow with ->save() which delete the record...
            $vault
                ->setIsRemoved(1)
                ->save();

            $payload = [
                "success" => true,
                "message" => p__("cabride", "This card is now deleted!"),
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
