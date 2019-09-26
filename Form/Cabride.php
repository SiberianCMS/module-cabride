<?php

namespace Cabride\Form;

use Siberian_Form_Abstract;
use Application_Model_Application as Application;
use Siberian\Currency;
use PaymentMethod\Model\Gateway;

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

        // Check if stripe is available
        $stripeIsAvailable = Gateway::has("stripe");

        $application = Application::getApplication();

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
        $currency = $this->addSimpleText(
            "readonly_currency",
            p__("cabride", "Currency (app settings)"));

        $appCurrency = $application->getCurrency();

        $currency
            ->setAttrib("readonly", "readonly");

        if (!empty($appCurrency)) {
            $currency->setValue($appCurrency . " " . Currency::getCurrency($appCurrency)["symbol"]);
        } else {
            $currency->setValue("-");
        }


        $currencyHelper = p__("payment_stripe", "Currency is set globally in <a style=\"font-weight: bold; text-decoration: underline;\" href=\"/application/customization_design_style/edit\">Editor > Design</a>");
        $currencyHelperHtml = <<<RAW
<div class="form-group sb-form-line">
    <label class="col-sm-3">&nbsp;</label>
    <div class="col-sm-7" 
         style="margin: 0 9px 0 7px;">
        <div class="alert alert-warning">
            $currencyHelper
        </div>
    </div>
</div>
RAW;

        $this->addSimpleHtml("currency_helper", $currencyHelperHtml);

        $distanceUnit = $this->addSimpleSelect(
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
            ["readonly_currency", "currency_helper", "distance_unit", "center_map", "center_map_hint"],
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



        $acceptedPaymentMethods = [
            "cash" => p__("cabride", "Cash"),
        ];

        if ($stripeIsAvailable) {
            $acceptedPaymentMethods = [
                "credit-card" => p__("cabride", "Credit card"),
                "cash" => p__("cabride", "Cash"),
                "all" => p__("cabride", "Credit card & Cash"),
            ];
        }

        $accepted_payments = $this->addSimpleSelect(
            "accepted_payments",
            p__("cabride", "Accepted payments"),
            $acceptedPaymentMethods);

        if ($stripeIsAvailable) {
            $paymentProvider = $this->addSimpleSelect(
                "payment_provider",
                p__("cabride", "Payment provider"),
                [
                    "stripe" => p__("cabride", "Stripe (Credit card)"),
                ]);
        }

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
