<?php

use Cabride\Model\Cabride;
use Cabride\Model\Driver;
use Cabride\Model\Payment;
use Cabride\Model\Payout;
use Cabride\Model\PayoutBulk;
use Core\Model\Base;
use Siberian\Exception;
use Siberian\File;
use Siberian\Json;
use Siberian\Mail;

/**
 * Class Cabride_PayoutController
 */
class Cabride_PayoutController extends Application_Controller_Default
{
    /**
     *
     */
    public function singleDriverAction()
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

            $payouts = (new Payment())->payoutForDriverId($driverId, ["unpaid"], $params);

            if (isset($payouts["total"]) && $payouts["total"] > 0) {
                $_payout = new Payout();
                $_payout
                    ->setDriverId($payouts["driver_id"])
                    ->setValueId($payouts["value_id"])
                    ->setAmount($payouts["total"])
                    ->setStatus("inprogress")
                    ->setPaymentIds($payouts["payment_ids"])
                    ->setPeriodFrom($payouts["period_from"])
                    ->setPeriodTo($payouts["period_to"])
                    ->save();

                // Update payments cash to "requested"
                $paymentIds = explode(",", $payouts["payment_ids"]);
                foreach ($paymentIds as $paymentId) {
                    $_payment = (new Payment())->find($paymentId);
                    if ($_payment->getId()) {
                        $_payment
                            ->setPayoutStatus("inprogress")
                            ->save();
                    }
                }

                // Notify driver by e-mail
                try {
                    $values = [
                        'title' => p__("cabride",
                            "You have a pending payout, please check inside the application for more details."),
                        'more' => p__("cabride", "The total amount to be paid out is %s",
                            Base::_formatPrice($payouts["total"], $cabride->getCurrency()))
                    ];

                    // Fake recipient for the smtp-sender!
                    $recipient = new Admin_Model_Admin();
                    $recipient
                        ->setEmail($payouts["email"])
                        ->setFirstname($payouts["firstname"])
                        ->setLastname($payouts["lastname"]);

                    // SMTP Mailer
                    (new Mail())
                        ->simpleEmail(
                            'cabride',
                            'pending_payout',
                            p__("cabride","You have a pending payout"),
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
    public function markAsPaidAction ()
    {
        try {
            $request = $this->getRequest();
            $payoutId = $request->getParam("payoutId", null);

            $payout = (new Payout())->find($payoutId);

            if (!$payout->getId()) {
                throw new Exception(p__("cabride", "This payout doesn't exists."));
            }

            // Update payments cash to "paid"
            $paymentIds = explode(",", $payout->getPaymentIds());
            foreach ($paymentIds as $paymentId) {
                $_payment = (new Payment())->find($paymentId);
                if ($_payment->getId()) {
                    $_payment
                        ->setPayoutStatus("paid")
                        ->save();
                }
            }

            $payout
                ->setStatus("paid")
                ->setPayoutDate(date("Y-m-d H:i:s"))
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

    /**
     *
     */
    public function bulkAction()
    {
        try {
            $request = $this->getRequest();
            $valueId = $request->getParam("valueId", null);
            $from = $request->getParam("from", null);
            $to = $request->getParam("to", null);

            $csvPath = PayoutBulk::generateBulk($valueId, $from, $to);

            $payload = [
                'success' => true,
                'message' => p__("cabride", 'Success'),
                'csvPath' => $csvPath,
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
     *
     */
    public function downloadCsvAction ()
    {
        try {
            $request = $this->getRequest();
            $bulkId = $request->getParam("bulkId", null);

            $bulk = (new PayoutBulk())->find($bulkId);

            if (!$bulk->getId()) {
                throw new Exception(p__("cabride", "This bulk export doesn't exists."));
            }

            // E-mail CSV to admins!
            $csvLines = Json::decode($bulk->getRawCsv());
            $csvTextLines = [];
            foreach ($csvLines as $csvLine) {
                $csvTextLines[] = join(";", $csvLine);
            }
            $csvText = join("\n", $csvTextLines);

            // tmp file to download!
            $csvPath = path("/var/tmp/" . uniqid() . ".csv");
            File::putContents($csvPath, $csvText);

            $this->_download($csvPath, "payout-bulk-{$bulkId}.csv");

        } catch (\Exception $e) {
            die($e->getMessage());
        }
    }

}
