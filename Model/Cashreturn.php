<?php

namespace Cabride\Model;

use Core\Model\Base;
use Siberian\Mail;

/**
 * Class Cashreturn
 * @package Cabride\Model
 *
 * @method integer getId()
 * @method Db\Table\Cashreturn getTable()
 */
class Cashreturn extends Base
{
    /**
     * @var string
     */
    protected $_db_table = Db\Table\Cashreturn::class;

    /**
     * @param $valueId
     * @return mixed
     * @throws \Zend_Exception
     */
    public function fetchArchives ($valueId)
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

            $drivers = (new Driver())->findAll(['value_id = ?' => $cabrideInstance->getValueId()]);

            if ($period === 'week' &&
                $weekDay === 1) {
                $from = date('Y-m-d 00:00:00', strtotime('-1 week'));
                $to = date('Y-m-d 23:59:59', strtotime('-1 day'));

                foreach ($drivers as $driver) {
                    $cron->log("[Cabride::Cashreturn:driver:{$driver->getId()}] {$from} [TO] {$to} \n");
                    self::generateBulk($driver->getId(), $from, $to);
                }
            } else if ($period === 'month' && $monthDay === 1) {
                $previousMonth = strtotime('-1 month');
                $from = date('Y-m-01 00:00:00', $previousMonth);
                $month = date('j', $previousMonth);
                $year = date('Y', $previousMonth);
                $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                $to = date("Y-m-{$daysInMonth} 23:59:59", $previousMonth);

                foreach ($drivers as $driver) {
                    $cron->log("[Cabride::Cashreturn:driver:{$driver->getId()}] {$from} [TO] {$to} \n");
                    self::generateBulk($driver->getId(), $from, $to);
                }
            } else {
                $cron->log("[Cabride::Cashreturn:period:{$cabrideInstance->getId()}] skipped until next {$period}. \n");
            }
        }
    }

    /**
     * @param $driverId
     * @param $from
     * @param $to
     * @throws \Zend_Exception
     */
    public static function generateBulk($driverId, $from, $to)
    {
        $driver = (new Driver())->find($driverId);
        $cabride = (new Cabride())->find($driver->getValueId(), "value_id");

        if (!$driver->getId()) {
            throw new \Exception(p__("cabride", "This driver doesn't exists."));
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
            $_cashReturn = new self();
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
                    'email_title' => p__('cabride', 'Cash return request!'),
                    'title' => p__("cabride",
                        "You have a new cash return request, please check inside the application for more details."),
                    'more' => p__("cabride", "The total amount to be returned is %s",
                        Base::_formatPrice($cashReturn["total"], $cabride->getCurrency()))
                ];

                // Fake recipient for the smtp-sender!
                $recipient = new \Admin_Model_Admin();
                $recipient
                    ->setEmail($cashReturn["email"])
                    ->setFirstname($cashReturn["firstname"])
                    ->setLastname($cashReturn["lastname"]);

                // SMTP Mailer
                (new Mail())
                    ->simpleEmail(
                        'cabride',
                        'cash_return_request',
                        p__('cabride', 'You have a new cash return request'),
                        [
                            $recipient
                        ],
                        $values,
                        explode(',', $cabride->getAdminEmails())[0])
                    ->send();
            } catch (\Exception $e) {
                // Unable to send e-mail
            }
        }
    }
}
