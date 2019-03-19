<?php

use Cabride\Model\Cabride;
use Cabride\Model\Driver;
use Cabride\Model\Payment;
use Cabride\Model\ClientVault;
use Siberian\Exception;
use Siberian\Json;

/**
 * Class Cabride_Mobile_DriverController
 */
class Cabride_Mobile_DriverController extends Application_Controller_Mobile_Default
{
    /**
     * Client route
     */
    public function paymentHistoryAction()
    {
        try {
            $request = $this->getRequest();
            $session = $this->getSession();
            $customerId = $session->getCustomerId();
            $optionValue = $this->getCurrentOptionValue();
            $valueId = $optionValue->getId();

            $cabride = (new Cabride())->find($valueId, "value_id");
            $driver = (new Driver())->find($customerId, "customer_id");
            $payments = (new Payment())->findAll([
                "driver_id = ?" => $driver->getId(),
                "status = ?" => "paid"
            ], "");

            $cashPayments = [];
            $cardPayments = [];
            foreach ($payments as $payment) {
                $data = $payment->getData();

                $vault = (new ClientVault())->find($payment->getClientVaultId());
                $vaultData = [
                    "brand" => $vault->getBrand(),
                    "ext" => $vault->getExp(),
                ];

                $data["vault"] = $vaultData;

                switch ($payment->getMethod()) {
                    case "credit-card":
                        $cardPayments[] = $data;
                        break;
                    case "cash":
                        $cashPayments[] = $data;
                        break;
                }
            }

            $payload = [
                "success" => true,
                "cashPayments" => $cashPayments,
                "cardPayments" => $cardPayments,
            ];
        } catch (\Exception $e) {
            $payload = [
                "error" => true,
                "message" => __("An unknown error occurred, please try again later."),
                "except" => $e->getMessage()
            ];
        }

        $this->_sendJson($payload);
    }
}
