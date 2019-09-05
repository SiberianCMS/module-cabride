<?php

use Cabride\Model\Cabride;
use Cabride\Model\Client;
use Cabride\Model\ClientVault;
use Cabride\Model\Payment;
use Cabride\Model\Request;
use Cabride\Model\Stripe\Currency;
use Customer_Model_Customer as Customer;
use Siberian\Exception;
use Siberian\Json;
use Cabride\Controller\Mobile as MobileController;

use Stripe\Stripe;
use Stripe\SetupIntent;
use Stripe\PaymentMethod;
use Stripe\PaymentIntent;
use Stripe\Customer as StripeCustomer;

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

    public function authorizePaymentAction ()
    {
        try {
            $application = $this->getApplication();
            $request = $this->getRequest();
            $optionValue = $this->getCurrentOptionValue();
            $valueId = $optionValue->getId();
            $customerId = $this->getSession()->getCustomerId();
            $requestId = $request->getParam("requestId", false);

            $cabride = (new Cabride())->find($optionValue->getId(), "value_id");
            $client = (new Client())->find($customerId, "customer_id");
            $customer = (new Customer())->find($client->getCustomerId());

            /**
             * @$ride Request
             */
            $ride = (new Request())->find($requestId);

            if (!$requestId || !$ride->getId()) {
                throw new Exception(p__("cabride",
                    "Sorry, we are unable to find this ride request!"));
            }

            if (!$client->getId()) {
                throw new Exception(p__("cabride",
                    "You session expired!"));
            }

            $stripeCost = round($ride->getEstimatedCost());

            // zero-decimals stripe currencies ....
            if (!in_array($cabride->getCurrency(), Currency::$zeroDecimals)) {
                $stripeCost = round($ride->getEstimatedCost() * 100);
            }

            $vaultId = $ride->getClientVaultId();
            $clientId = $ride->getClientId();

            $vault = (new ClientVault())->find($vaultId);

            // Create the payment
            $payment = new Payment();

            Stripe::setApiKey($cabride->getStripeSecretKey());
            $stripeCustomer = StripeCustomer::retrieve($client->getStripeCustomerToken());

            $paymentIntent = PaymentIntent::create([
                "amount" => $stripeCost,
                "currency" => $cabride->getCurrency(),
                "capture_method" => "manual",
                "customer" => $stripeCustomer["id"],
                "payment_method" => $vault->getPaymentMethod(),
                "metadata" => [
                    "request_id" => $ride->getId(),
                    "client_id" => $clientId,
                    "value_id" => $valueId,
                    "app_id" => $application->getId(),
                ]
            ]);

            $payment
                ->setBrand($vault->getBrand())
                ->setExp($vault->getExp())
                ->setLast($vault->getLast())
                ->setProvider("stripe")
                ->setValueId($valueId)
                ->setRequestId($ride->getId())
                ->setClientId($client->getId())
                ->setClientVaultId($ride->getClientVaultId())
                ->setAmountAuthorized($ride->getEstimatedCost())
                ->setAmountAuthorizedIntent($stripeCost)
                ->setCurrency($cabride->getCurrency())
                ->setMethod($ride->getPaymentType())
                ->setStatus($paymentIntent["status"])
                ->setStripePaymentIntent($paymentIntent["id"])
                ->save();

            if ($paymentIntent["status"] !== "requires_capture") {
                throw new Exception(p__("cabride",
                    "The authorization was declined, '%s'",
                    $paymentIntent["status"]));
            }

            $payload = [
                "success" => true,
                "paymentIntent" => $paymentIntent
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
                $stripeCustomer = StripeCustomer::retrieve($client->getStripeCustomerToken());
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
                "message" => $e->getMessage(),
                "trace" => $e->getTrace()
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
                $stripeCustomer = StripeCustomer::retrieve($client->getStripeCustomerToken());
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

            \Stripe\Stripe::setApiKey($cabride->getStripeSecretKey());

            // Delete the card from the Stripe customer!
            \Stripe\Customer::deleteSource($client->getStripeCustomerToken(), $vault->getCardToken());

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
