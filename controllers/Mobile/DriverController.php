<?php

use Cabride\Model\Cabride;
use Cabride\Model\Driver;
use Cabride\Model\Payment;
use Cabride\Model\ClientVault;
use Cabride\Model\Cashreturn;
use Cabride\Model\Payout;
use Core\Model\Base;
use Cabride\Controller\Mobile as MobileController;

/**
 * Class Cabride_Mobile_DriverController
 */
class Cabride_Mobile_DriverController extends MobileController
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
            $payments = (new Payment())->findAll(
                [
                    "driver_id = ?" => $driver->getId(),
                    "status = ?" => "paid",
                ],
                "payment_id DESC",
                [
                    "limit" => 100
                ]
            );

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

                $data["formatted_amount"] = Base::_formatPrice($data["amount"], $cabride->getCurrency());
                $data["formatted_commission_amount"] = Base::_formatPrice($data["commission_amount"], $cabride->getCurrency());
                $data["formatted_payout"] = Base::_formatPrice($data["amount"] - $data["commission_amount"], $cabride->getCurrency());

                switch ($payment->getMethod()) {
                    case "credit-card":
                        $cardPayments[] = $data;
                        break;
                    case "cash":
                        $cashPayments[] = $data;
                        break;
                }
            }

            $cashReturns = (new Cashreturn())->findAll(
                [
                    "driver_id = ?" => $driver->getId(),
                    "status = ?" => "requested",
                ]
            );

            $dataCashReturns = [];
            foreach ($cashReturns as $cashReturn) {
                $data = $cashReturn->getData();

                $data["formatted_total"] = Base::_formatPrice($data["amount"], $cabride->getCurrency());
                $data["period_from_timestamp"] = datetime_to_format($data["period_from"], Zend_Date::TIMESTAMP);
                $data["period_to_timestamp"] = datetime_to_format($data["period_to"], Zend_Date::TIMESTAMP);

                $dataCashReturns[] = $data;
            }

            $payoutPeriod = $cabride->getPayoutPeriod();
            $payoutPeriodText = '';
            if ($payoutPeriod === 'week') {
                $payoutPeriodText = 'per week';
            } else if ($payoutPeriod === 'month') {
                $payoutPeriodText = 'per month';
            }

            $payouts = (new Payout())->findAll(
                [
                    "driver_id = ?" => $driver->getId(),
                    "status = ?" => "inprogress",
                ]
            );

            $dataPayouts = [];
            foreach ($payouts as $payout) {
                $data = $payout->getData();

                $data["formatted_total"] = Base::_formatPrice($data["amount"], $cabride->getCurrency());
                $data["period_from_timestamp"] = datetime_to_format($data["period_from"], Zend_Date::TIMESTAMP);
                $data["period_to_timestamp"] = datetime_to_format($data["period_to"], Zend_Date::TIMESTAMP);

                $dataPayouts[] = $data;
            }

            $payload = [
                "success" => true,
                "collections" => [
                    "cashPayments" => $cashPayments,
                    "cardPayments" => $cardPayments,
                ],
                "wording" => [
                    "paymentsPeriod" => p__('cabride', $payoutPeriodText)
                ],
                "cashReturns" => $dataCashReturns,
                "pendingPayouts" => $dataPayouts,
            ];
        } catch (\Exception $e) {
            $payload = [
                "error" => true,
                "message" => $e->getMessage(),
            ];
        }

        $this->_sendJson($payload);
    }
}
