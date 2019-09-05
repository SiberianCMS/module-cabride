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
    public function bulkAction()
    {
        try {
            $request = $this->getRequest();
            $valueId = $request->getParam("valueId", null);
            $from = $request->getParam("from", null);
            $to = $request->getParam("to", null);

            $cabride = (new Cabride())->find($valueId, "value_id");

            $params = null;
            if (!empty($from) && !empty($to)) {
                $params = [
                    "from" => $from,
                    "to" => $to,
                ];
            }

            $payouts = (new Payment())->aggregatePayout($valueId, $params);

            $payoutIds = [];
            $driverIds = [];
            $bulkPaymentIds = [];
            $grandTotal = 0;

            $csvLines = [
                [
                    "Driver ID",
                    "Payout ID",
                    "Driver",
                    "E-mail",
                    "Period from",
                    "Period to",
                    "Payment IDs",
                    "Total commission due",
                ]
            ];
            foreach ($payouts as $payout) {
                $_payout = new Payout();
                $_payout
                    ->setDriverId($payout->getDriverId())
                    ->setValueId($valueId)
                    ->setAmount(round($payout->getTotal(), 2))
                    ->setStatus("inprogress")
                    ->setPaymentIds($payout->getPaymentIds())
                    ->setPeriodFrom($payout->getPeriodFrom())
                    ->setPeriodTo($payout->getPeriodTo())
                    ->save();

                // Update payment to "inprogress"
                $paymentIds = explode(",", $payout->getPaymentIds());
                foreach ($paymentIds as $paymentId) {
                    $_payment = (new Payment())->find($paymentId);
                    if ($_payment->getId()) {
                        $_payment
                            ->setPayoutStatus("inprogress")
                            ->save();
                    }
                }

                $payoutIds[] = $_payout->getId();
                $driverIds[] = $_payout->getDriverId();
                $bulkPaymentIds[] = $_payout->getPaymentIds();

                $csvLines[] = [
                    $payout->getDriverId(),
                    $payout->getId(),
                    str_replace(";", "-", $payout->getFirstname() . " " . $payout->getLastname()),
                    $payout->getEmail(),
                    $payout->getPeriodFrom(),
                    $payout->getPeriodTo(),
                    $payout->getPaymentIds(),
                    round($payout->getTotal(), 2),
                ];

                $grandTotal += round($payout->getTotal(), 2);

                // Notify driver by e-mail
                try {
                    $values = [
                        'title' => p__("cabride",
                            "You have a pending payout, please check inside the application for more details."),
                        'more' => p__("cabride", "The total amount to be paid out is %s",
                            Base::_formatPrice($payout->getTotal(), $cabride->getCurrency()))
                    ];

                    // Fake recipient for the smtp-sender!
                    $recipient = new Admin_Model_Admin();
                    $recipient
                        ->setEmail($payout->getEmail())
                        ->setFirstname($payout->getFirstname())
                        ->setLastname($payout->getLastname);

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

            $bulk = new PayoutBulk();
            $bulk
                ->setValueId($valueId)
                ->setDriverIds(join(",", $driverIds))
                ->setPaymentIds(join(",", $bulkPaymentIds))
                ->setPayoutIds(join(",", $payoutIds))
                ->setTotal($grandTotal)
                ->setRawCsv(Json::encode($csvLines));

            // Add period if defined!
            if (is_array($params)) {
                $bulk
                    ->setPeriodFrom($params["from"])
                    ->setPeriodTo($params["to"]);
            }

            $bulk->save();

            // E-mail CSV to admins!
            $csvTextLines = [];
            foreach ($csvLines as $csvLine) {
                $csvTextLines[] = join(";", $csvLine);
            }
            $csvText = join("\n", $csvTextLines);

            // uniqid
            $csvPath = rpath("/var/tmp/" . uniqid() . ".csv");
            File::putContents($csvPath, $csvText);

            $payload = [
                "success" => true,
                "message" => __("Success"),
                "csvPath" => $csvPath,
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