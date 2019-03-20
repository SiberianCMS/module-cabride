<?php

use Cabride\Model\Cabride;
use Cabride\Model\Driver;
use Cabride\Model\Payment;
use Cabride\Model\Cashreturn;
use Core\Model\Base;
use Siberian\Exception;
use Siberian\Mail;

/**
 * Class Cabride_CashreturnController
 */
class Cabride_CashreturnController extends Application_Controller_Default
{
    /**
     * @throws \Siberian\Zend_Layout_Exception
     */
    public function requestCashReturnAction()
    {
        try {
            $request = $this->getRequest();
            $driverId = $request->getParam("driverId", null);
            $from = $request->getParam("from", null);
            $to = $request->getParam("to", null);

            $driver = (new Driver())->find($driverId);
            $cabride = (new Cabride())->find($driver->getValueId(), "value_id");
            
            if (!$driver->getId()) {
                throw new Exception(p__("cabride", "This driver doesn't exists."));
            }

            $params = null;
            if (!empty($from) && !empty($to)) {
                $params = [
                    "from" => $from,
                    "to" => $to,
                ];
            }
            
            $cashReturn = (new Payment())->cashReturnForDriverId($driverId, ["toreturn"], $params);
            
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

                // Notify driver by e-mail
                try {
                    $values = [
                        'title' => p__("cabride",
                            "You have a new cash return request, please check inside the application for more details."),
                        'more' => p__("cabride", "The total amount to be returned is %s",
                            Base::_formatPrice($cashReturn["total"], $cabride->getCurrency()))
                    ];

                    // Fake recipient for the smtp-sender!
                    $recipient = new Admin_Model_Admin();
                    $recipient
                        ->setEmail($cashReturn["email"])
                        ->setFirstname($cashReturn["firstname"])
                        ->setLastname($cashReturn["lastname"]);

                    // SMTP Mailer
                    (new Mail())
                        ->simpleEmail(
                            'cabride',
                            'cash_return_request',
                            p__("cabride","You have a new cash return request"),
                            [
                                $recipient
                            ],
                            $values,
                            explode(",", $cabride->getAdminEmails())[0])
                        ->send();
                } catch (\Exception $e) {
                    // Unable to send e-mail
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