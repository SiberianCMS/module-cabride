<?php

namespace Cabride\Model;

use Core\Model\Base;
use Siberian\File;
use Siberian\Json;
use Siberian\Mail;

/**
 * Class PayoutBulk
 * @package Cabride\Model
 *
 * @method integer getId()
 */
class PayoutBulk extends Base
{
    /**
     * @var string
     */
    protected $_db_table = Db\Table\PayoutBulk::class;

    /**
     * @param $valueId
     * @return mixed
     */
    public function fetchArchives($valueId)
    {
        return $this->getTable()->fetchArchives($valueId);
    }

    /**
     * @param $cron
     * @throws \Zend_Exception
     */
    public static function toGenerate($cron)
    {
        // Fetching all weekly/monthly cabrides
        $cabrideInstances = (new Cabride())->findAll([
            'payout_period IN (?)' => ['week', 'month']
        ]);

        foreach ($cabrideInstances as $cabrideInstance) {
            $period = $cabrideInstance->getPayoutPeriod();
            $weekDay = (int) date('w');
            $monthDay = (int) date('j');
            if ($period === 'week' &&
                $weekDay === 1) {
                $from = date('Y-m-d 00:00:00', strtotime('-1 week'));
                $to = date('Y-m-d 23:59:59', strtotime('-1 day'));

                $cron->log("[Cabride::PayoutBulk:period:{$cabrideInstance->getId()}] {$from} [TO] {$to} \n");
                self::generateBulk($cabrideInstance->getValueId(), $from, $to);
            } else if ($period === 'month' && $monthDay === 1) {
                $previousMonth = strtotime('-1 month');
                $from = date('Y-m-01 00:00:00', $previousMonth);
                $month = date('j', $previousMonth);
                $year = date('Y', $previousMonth);
                $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                $to = date("Y-m-{$daysInMonth} 23:59:59", $previousMonth);

                $cron->log("[Cabride::PayoutBulk:period:{$cabrideInstance->getId()}] {$from} [TO] {$to} \n");
                self::generateBulk($cabrideInstance->getValueId(), $from, $to);
            } else {
                $cron->log("[Cabride::PayoutBulk:period:{$cabrideInstance->getId()}] skipped until next {$period}. \n");
            }
        }
    }

    /**
     * @param $valueId
     * @param $from
     * @param $to
     * @return string
     * @throws \Zend_Exception
     */
    public static function generateBulk($valueId, $from, $to): string
    {
        $cabride = (new Cabride())->find($valueId, 'value_id');

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
                $recipient = new \Admin_Model_Admin();
                $recipient
                    ->setEmail($payout->getEmail())
                    ->setFirstname($payout->getFirstname())
                    ->setLastname($payout->getLastname);

                // SMTP Mailer
                (new Mail())
                    ->simpleEmail(
                        'cabride',
                        'pending_payout',
                        p__("cabride", "You have a pending payout"),
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

        $bulk = new self();
        $bulk
            ->setValueId($valueId)
            ->setDriverIds(implode(",", $driverIds))
            ->setPaymentIds(implode(",", $bulkPaymentIds))
            ->setPayoutIds(implode(",", $payoutIds))
            ->setTotal($grandTotal)
            ->setRawCsv(Json::encode($csvLines));

        // Add period if defined!
        if (is_array($params)) {
            $bulk
                ->setPeriodFrom($params['from'])
                ->setPeriodTo($params['to']);
        }

        $bulk->save();

        // E-mail CSV to admins!
        $csvTextLines = [];
        foreach ($csvLines as $csvLine) {
            $csvTextLines[] = implode(";", $csvLine);
        }
        $csvText = implode("\n", $csvTextLines);

        // uniqid
        $csvPath = rpath('/var/tmp/' . uniqid('bulk_', true) . '.csv');
        File::putContents($csvPath, $csvText);

        return $csvPath;
    }
}
