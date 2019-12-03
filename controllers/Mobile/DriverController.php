<?php

use Cabride\Model\Cabride;
use Cabride\Model\Driver;
use Cabride\Model\Payment;
use Cabride\Model\Cashreturn;
use Cabride\Model\Payout;
use Core\Model\Base;
use Cabride\Controller\Mobile as MobileController;
use Siberian\Exception;

/**
 * Class Cabride_Mobile_DriverController
 */
class Cabride_Mobile_DriverController extends MobileController
{
    /**
     * @api
     * @link /cabride/mobile_driver/payment-history
     *
     * Client route
     */
    public function paymentHistoryAction()
    {
        try {
            $session = $this->getSession();
            $customerId = $session->getCustomerId();
            $optionValue = $this->getCurrentOptionValue();
            $valueId = $optionValue->getId();

            $cabride = (new Cabride())->find($valueId, 'value_id');
            if (!$cabride || !$cabride->getId()) {
                throw new Exception(p__('cabride', "This instance doesn't exists."));
            }

            $driver = (new Driver())->find($customerId, 'customer_id');
            if (!$driver || !$driver->getId()) {
                throw new Exception(p__('cabride', "This driver doesn't exists."));
            }

            $payments = (new Payment())->findAll(
                [
                    'driver_id = ?' => $driver->getId(),
                    'status = ?' => 'paid',
                ],
                'payment_id DESC',
                [
                    'limit' => 100
                ]
            );

            $allPayments = [];
            foreach ($payments as $payment) {
                $data = $payment->getData();

                $data['formatted_amount'] = Base::_formatPrice($data['amount'], $cabride->getCurrency());
                $data['formatted_commission_amount'] = Base::_formatPrice($data['commission_amount'], $cabride->getCurrency());
                $data['formatted_payout'] = Base::_formatPrice($data['amount'] - $data['commission_amount'], $cabride->getCurrency());

                // Grouping payments by method!
                $method = $payment->getMethod();
                if (!array_key_exists($method, $allPayments)) {
                    $allPayments[$method] = [];
                }
                $allPayments[$method][] = $data;
            }

            $cashReturns = (new Cashreturn())->findAll(
                [
                    'driver_id = ?' => $driver->getId(),
                    'status = ?' => 'requested',
                ]
            );
            $dataCashReturns = $this->_getDataPayments($cabride, $cashReturns);

            $payouts = (new Payout())->findAll(
                [
                    'driver_id = ?' => $driver->getId(),
                    'status = ?' => 'inprogress',
                ]
            );
            $dataPayouts = $this->_getDataPayments($cabride, $payouts);

            $payload = [
                'success' => true,
                'collections' => [
                    'allPayments' => $allPayments,
                ],
                'cashReturns' => $dataCashReturns,
                'pendingPayouts' => $dataPayouts,
            ];
        } catch (\Exception $e) {
            $payload = [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     * @param $cabride
     * @param $collection
     * @return array
     * @throws Zend_Date_Exception
     * @throws Zend_Exception
     * @throws Zend_Locale_Exception
     */
    private function _getDataPayments($cabride, $collection): array
    {
        $formattedPayments = [];
        foreach ($collection as $item) {
            $data = $item->getData();

            $data['formatted_total'] = Base::_formatPrice($data['amount'], $cabride->getCurrency());
            $data['period_from_timestamp'] = datetime_to_format($data['period_from'], Zend_Date::TIMESTAMP);
            $data['period_to_timestamp'] = datetime_to_format($data['period_to'], Zend_Date::TIMESTAMP);

            $formattedPayments[] = $data;
        }
        return $formattedPayments;
    }
}
