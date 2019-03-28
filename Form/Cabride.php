<?php

namespace Cabride\Form;

use Siberian_Form_Abstract;
use Cabride\Model\Stripe\Currency;

/**
 * Class Cabride
 * @package Cabride\Form
 */
class Cabride extends Siberian_Form_Abstract
{
    /**
     * @throws \Zend_Form_Exception
     */
    public function init()
    {
        parent::init();

        $this
            ->setAction(__path("/cabride/application/editpost"))
            ->setAttrib("id", "form-cabride");

        // Bind as a create form!
        self::addClass("create", $this);

        $this->addSimpleHidden("cabride_id");

        // Builds the default form from schema!
        $this->addSimpleHidden("value_id");

        $adminEmails = $this->addSimpleText(
            "admin_emails",
            p__("cabride", "Admin e-mails (coma separated)"));

        $currency = $this->addSimpleSelect(
            "currency",
            p__("cabride", "Currency"),
            array_combine(Currency::$supported, Currency::$supported));

        $distance_unit = $this->addSimpleSelect(
            "distance_unit",
            p__("cabride", "Distance unit"),
            [
                "km" => __("Km"),
                "mi" => __("Miles"),
            ]);

        $this->addSimpleText("map_center", p__("cabride", "Center map address"));

        $search_timeout = $this->addSimpleNumber(
            "search_timeout",
            p__("cabride", "Search timeout (seconds)"),
            60,
            3600,
            true,
            1);
        $search_timeout->setRequired(true);

        $search_radius = $this->addSimpleNumber(
            "search_radius",
            p__("cabride", "Search radius (km/mi)"),
            10,
            5000,
            true,
            1);
        $search_radius->setRequired(true);

        $accepted_payments = $this->addSimpleSelect(
            "accepted_payments",
            p__("cabride", "Accepted payments"),
            [
                "credit-card" => p__("cabride", "Credit card"),
                "cash" => p__("cabride", "Cash"),
                "all" => p__("cabride", "Credit card & Cash"),
            ]);

        $paymentProvider = $this->addSimpleSelect(
            "payment_provider",
            p__("cabride", "Payment provider"),
            [
                "stripe" => p__("cabride", "Stripe (Credit card)"),
                //"twocheckout" => p__("cabride", "2 Checkout (Credit card)"),
                //"braintree" => p__("cabride", "BrainTree (Credit card & PayPal)"),
            ]);

        $commissionType = $this->addSimpleSelect(
            "commission_type",
            p__("cabride", "Commission type"),
            [
                "disabled" => p__("cabride", "Disabled"),
                "fixed" => p__("cabride", "Fixed amount"),
                "percentage" => p__("cabride", "Percentage"),
                "mixed" => p__("cabride", "Fixed amount + percentage"),
            ]);

        $commissionFixed = $this->addSimpleText("commission_fixed", p__("cabride", "Commission (fixed)"));
        $commissionFixed->setRequired(true);

        $commission = $this->addSimpleText("commission", p__("cabride", "Commission (percentage)"));
        $commission->setRequired(true);

        $course_mode = $this->addSimpleSelect(
            "course_mode",
            p__("cabride", "Course mode"),
            [
                "immediate" => p__("cabride", "Immediate"),
                //"all" => p__("cabride", "Immediate & Scheduled"),
            ]);

        $pricing_mode = $this->addSimpleSelect(
            "pricing_mode",
            p__("cabride", "Pricing mode"),
            [
                "fixed" => p__("cabride", "Fixed by vehicle type (Admin)"),
                "driver" => p__("cabride", "Fixed by the drivers"),
            ]);

        $driver_can_register = $this->addSimpleCheckbox("driver_can_register", p__("cabride", "Driver can register"));

        $save = $this->addSubmit(p__("cabride", "Save"), p__("cabride", "Save"));
        $save->addClass("pull-right");
    }

    /**
     * @param $cabrideId
     */
    public function setCabrideId($cabrideId)
    {
        $this
            ->getElement("cabride_id")
            ->setValue($cabrideId)
            ->setRequired(true);
    }
}
