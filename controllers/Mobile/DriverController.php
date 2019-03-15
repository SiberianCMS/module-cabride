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
            ]);

            $cashPayments = [];
            $cashReturnedPayments = [];
            $cardPayments = [];
            $cardPaidoutPayments = [];
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
                        if ($payment->getPayoutStatus() === "paid") {
                            $cardPaidoutPayments[] = $data;
                        } else {
                            $cardPayments[] = $data;
                        }
                        break;
                    case "cash":
                        if ($payment->getReturnStatus() === "returned") {
                            $cashReturnedPayments[] = $data;
                        } else {
                            $cashPayments[] = $data;
                        }
                        break;
                }
            }

            $payload = [
                "success" => true,
                "cashPayments" => $cashPayments,
                "cashReturnedPayments" => $cashReturnedPayments,
                "cardPayments" => $cardPayments,
                "cardPaidoutPayments" => $cardPaidoutPayments,
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
