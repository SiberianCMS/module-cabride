<?php

namespace Cabride\Form;

use Siberian_Form_Abstract;

/**
 * Class Vehicle
 * @package Cabride\Form
 */
class Vehicle extends Siberian_Form_Abstract
{
    /**
     * @throws \Zend_Form_Exception
     */
    public function init()
    {
        parent::init();

        $this
            ->setAction(__path("/cabride/vehicle/editpost"))
            ->setAttrib("id", "form-cabride-vehicle");

        // Bind as a create form!
        self::addClass("create", $this);

        $this->addSimpleHidden("vehicle_id");
        $this->addSimpleHidden("value_id");

        // Builds the default form from schema!
        
        $type = $this->addSimpleText("type", p__("cabride", "Type"));
        $type->setRequired(true);

        $icon = $this->addSimpleImage("icon", p__("cabride", "Icon"), p__("cabride", "Icon"), [
            "width" => 512,
            "height" => 512,
        ]);
        $icon->setRequired(true);

        $pricingText = p__("cabride", "The pricing is set by the driver, you can't edit the values.");
        $pricingHtml = <<<HTML
<div class="col-md-10">
    <div class="alert alert-warning">
        {$pricingText}
    </div>
</div>
HTML;

        $this->addSimpleHtml("pricing_disabled", $pricingHtml);

        $base_fare = $this->addSimpleNumber("base_fare", p__("cabride", "Base fare"),
            0, null, true, 0.01);
        $base_fare->setRequired(true);

        $distance_fare = $this->addSimpleNumber("distance_fare", p__("cabride", "Distance fare (each km/mi)"),
            0, null, true, 0.01);
        $distance_fare->setRequired(true);

        $time_fare = $this->addSimpleNumber("time_fare", p__("cabride", "Time fare (every minute)"),
            0, null, true, 0.01);
        $time_fare->setRequired(true);

        $is_visible = $this->addSimpleCheckbox("is_visible", p__("cabride", "Is visible"));
        $is_visible->setRequired(true);

        $save = $this->addSubmit(p__("cabride", "Save"), p__("cabride", "Save"));
        $save->addClass("pull-right");
    }

    /**
     * @param $vehicle_id
     */
    public function setVehicleId($vehicle_id)
    {
        $this->getElement("vehicle_id")->setValue($vehicle_id)->setRequired(true);
    }

    /**
     * @return $this
     * @throws \Zend_Form_Exception
     */
    public function disablePricing()
    {
        $els = [
            "base_fare",
            "distance_fare",
            "time_fare",
        ];
        foreach ($els as $el) {
            $_tmpEl = $this->getElement($el);
            $_tmpEl->addClass("disabled");
            $_tmpEl->setAttrib("disabled", "disabled");
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function disableFares ()
    {
        $els = [
            "base_fare",
            "distance_fare",
            "time_fare",
        ];
        foreach ($els as $el) {
            $_tmpEl = $this->getElement($el)->setRequired(false);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function removePricingHtml()
    {
        $this->removeElement("pricing_disabled");

        return $this;
    }
}
