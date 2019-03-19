<?php

use Cabride\Model\Driver;
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
            
            $driver = (new Driver())->find($driverId);
            
            if (!$driver->getId()) {
                throw new Exception(p__("cabride", "This driver doesn't exists."));
            }
            
            $cashReturn = (new Payment())->cashReturnForDriverId($driverId);
            
            if (isset($cashReturn["total"]) && $cashReturn["total"] > 0) {
                $_cashReturn = new Cashreturn();
                $_cashReturn
                    ->setDriverId($cashReturn["driver_id"])
                    ->setValueId($cashReturn["value_id"])
                    ->setAmount($cashReturn["total"])
                    ->setStatus("requested")
                    ->setPaymentIds($cashReturn["payment_ids"])
                    ->setPeriodFrom($cashReturn["period_from"])
                    ->setPeriodTo($cashReturn["period_to"])
                    ->save();
                
                // Update payments cash to "requested"
                $paymentIds = explode(",", $cashReturn["payment_ids"]);
                foreach ($paymentIds as $paymentId) {
                    $_payment = (new Payment())->find($paymentId);
                    if ($_payment->getId()) {
                        $_payment
                            ->setReturnStatus("requested")
                            ->save();
                    }
                }
            }
            
            $payload = [
                "success" => true,
                "message" => __("Success"),
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
                "message" => __("Success"),
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