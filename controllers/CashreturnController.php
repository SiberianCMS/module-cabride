<?php

use Cabride\Model\Payment;
use Cabride\Model\Cashreturn;
use Siberian\Exception;

/**
 * Class Cabride_CashreturnController
 */
class Cabride_CashreturnController extends Application_Controller_Default
{
    /**
     *
     */
    public function requestCashReturnAction()
    {
        try {
            $request = $this->getRequest();
            $driverId = $request->getParam("driverId", null);
            $from = $request->getParam("from", null);
            $to = $request->getParam("to", null);

            Cashreturn::generateBulk($driverId, $from, $to);

            $payload = [
                "success" => true,
                "message" => p__("cabride", "Success"),
            ];
        } catch (\Exception $e) {
            $payload = [
                "error" => true,
                "message" => $e->getMessage(),
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     * 
     */
    public function markAsReturnedAction ()
    {
        try {
            $request = $this->getRequest();
            $cashReturnId = $request->getParam("cashReturnId", null);

            $cashReturn = (new Cashreturn())->find($cashReturnId);

            if (!$cashReturn->getId()) {
                throw new Exception(p__("cabride", "This cash return request doesn't exists."));
            }

            // Update payments cash to "requested"
            $paymentIds = explode(",", $cashReturn->getPaymentIds());
            foreach ($paymentIds as $paymentId) {
                $_payment = (new Payment())->find($paymentId);
                if ($_payment->getId()) {
                    $_payment
                        ->setReturnStatus("returned")
                        ->save();
                }
            }

            $cashReturn
                ->setStatus("returned")
                ->setReturnDate(date("Y-m-d H:i:s"))
                ->save();
            
            $payload = [
                "success" => true,
                "message" => p__("cabride", "Success"),
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
