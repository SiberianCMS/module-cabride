<?php

namespace Cabride\Form;

use Siberian_Form_Abstract;
use Cabride\Model\Stripe\Currency;
use Cabride\Model\Timezone;

/**
 * Class Cabride
 * @package Cabride\Form
 */
class Cabride extends Siberian_Form_Abstract
{
    /**
     * @throws \Zend_Form_Exception
     * @throws \Zend_Validate_Exception
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

        $this->groupElements("admin", ["admin_emails"], p__("cabride", "Contact"));

        // All currencies
        $currency = $this->addSimpleSelect(
            "currency",
            p__("cabride", "Currency"),
            Currency::getAllCurrencies());

        //$timezone = $this->addSimpleSelect(
        //    "timezone",
        //    p__("cabride", "Timezone"),
        //    Timezone::getTranslated());

        $distance_unit = $this->addSimpleSelect(
            "distance_unit",
            p__("cabride", "Distance unit"),
            [
                "km" => __("Km"),
                "mi" => __("Miles"),
            ]);

        $this->addSimpleText("center_map", p__("cabride", "Center map address"));

        $centerMapHint = p__("cabride", "Leave blank to center map on user GPS position.");
        $html = <<<RAW
<div class="form-group sb-form-line">
    <label class="col-sm-3">&nbsp;</label>
    <div class="col-sm-7" 
         style="margin: 0 9px 0 7px;">
        <div class="alert alert-warning">
            $centerMapHint
        </div>
    </div>
</div>
RAW;

        $this->addSimpleHtml("center_map_hint", $html);

        $this->groupElements("localization",
            ["currency", "distance_unit", "center_map", "center_map_hint"],
            p__("cabride", "Localization"));

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

        $course_mode = $this->addSimpleSelect(
            "course_mode",
            p__("cabride", "Course mode"),
            [
                "immediate" => p__("cabride", "Immediate"),
                //"all" => p__("cabride", "Immediate & Scheduled"),
            ]);

        $this->groupElements("rides",
            ["search_timeout", "search_radius", "course_mode"],
            p__("cabride", "Rides"));

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

        $commissionFixed = $this->addSimpleNumber("commission_fixed", p__("cabride", "Commission (fixed)"), 0, null, true);
        $commissionFixed->setRequired(true);

        $commission = $this->addSimpleNumber("commission", p__("cabride", "Commission (percentage)"), 0, 100, true);
        $commission->setRequired(true);

        $pricing_mode = $this->addSimpleSelect(
            "pricing_mode",
            p__("cabride", "Pricing mode"),
            [
                "fixed" => p__("cabride", "Fixed by vehicle type (Admin)"),
                "driver" => p__("cabride", "Fixed by the drivers"),
            ]);

        $this->groupElements("payments",
            ["accepted_payments", "payment_provider", "commission_type", "commission_fixed", "commission", "pricing_mode"],
            p__("cabride", "Payments"));

        // DESIGN!
        $this->addSimpleImage(
            "nav_background",
            p__("cabride", "Menu background"),
            p__("cabride", "Menu background"),
            [
                "width" => 460,
                "height" => 340,
            ]
        );

        $this->addSimpleImage(
            "passenger_picture",
            p__("cabride", "Passenger picture"),
            p__("cabride", "Passenger picture"),
            [
                "width" => 512,
                "height" => 512,
            ]
        );

        $this->addSimpleImage(
            "driver_picture",
            p__("cabride", "Driver picture"),
            p__("cabride", "Driver picture"),
            [
                "width" => 512,
                "height" => 512,
            ]
        );

        $this->groupElements("design",
            ["nav_background_button", "passenger_picture_button", "driver_picture_button"],
            p__("cabride", "Design"));


        // @todo work in progress
        // PASSENGER PRIVACY
        //$this->addSimpleCheckbox("show_passenger_photo", p__("cabride", "Show passenger picture"));
        //$this->addSimpleCheckbox("show_passenger_name", p__("cabride", "Show passenger name"));
        //$this->addSimpleCheckbox("show_passenger_phone", p__("cabride", "Show passenger phone"));
//
        //$this->groupElements("privacy",
        //    ["show_passenger_photo", "show_passenger_name", "show_passenger_phone"],
        //    p__("cabride", "Passenger privacy"));


        // MISC
        $this->addSimpleCheckbox("driver_can_register", p__("cabride", "Driver can register"));

        $this->groupElements("misc",
            ["driver_can_register"],
            p__("cabride", "Misc"));

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
