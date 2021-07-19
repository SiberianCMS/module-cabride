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

        $seats = $this->addSimpleNumber("seats", p__('cabride', 'Number of seats.'));
        $seats->setValue(3);

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

        // Seat fares
        $extra_seat_fare = $this->addSimpleNumber("extra_seat_fare", p__("cabride", "Extra fare for every seat after the first."),
            0, null, true, 0.01);
        $extra_seat_fare->setRequired(true);
        $extra_seat_fare->setDescription(p__('cabride', 'Set to 0 to disable extra seat fares'));

        $seat_distance_fare = $this->addSimpleNumber("seat_distance_fare", p__("cabride", "Extra distance fare (each km/mi) for every seat after the first."),
            0, null, true, 0.01);
        $seat_distance_fare->setRequired(true);
        $seat_distance_fare->setDescription(p__('cabride', 'Set to 0 to disable extra seat fares'));

        $seat_time_fare = $this->addSimpleNumber("seat_time_fare", p__("cabride", "Extra time fare (every minute) for every seat after the first."),
            0, null, true, 0.01);
        $seat_time_fare->setRequired(true);
        $seat_time_fare->setDescription(p__('cabride', 'Set to 0 to disable extra seat fares'));

        $this->groupElements('group_course', [
            'base_fare',
            'distance_fare',
            'time_fare',
            'extra_seat_fare',
            'seat_distance_fare',
            'seat_time_fare',
        ], p__('cabride', 'Course fares'));

        // Tour fares
        $tour_base_fare = $this->addSimpleNumber("tour_base_fare", p__("cabride", "Tour base fare."),
            0, null, true, 0.01);
        $tour_base_fare->setRequired(true);
        $tour_base_fare->setDescription(p__('cabride', 'Set to 0 to disable base fare'));

        $tour_time_fare = $this->addSimpleNumber("tour_time_fare", p__("cabride", "Fare for every time frame."),
            0, null, true, 0.01);
        $tour_time_fare->setRequired(true);
        $tour_time_fare->setDescription(p__('cabride', 'Time frame is 30 minutes.'));

        $extra_seat_tour_base_fare = $this->addSimpleNumber("extra_seat_tour_base_fare", p__("cabride", "Extra base fare for every seat after the first."),
            0, null, true, 0.01);
        $extra_seat_tour_base_fare->setRequired(true);
        $extra_seat_tour_base_fare->setDescription(p__('cabride', 'Set to 0 to disable extra seat fares'));

        $extra_seat_tour_time_fare = $this->addSimpleNumber("extra_seat_tour_time_fare", p__("cabride", "Extra time frame fare (every 30 minutes) for every seat after the first."),
            0, null, true, 0.01);
        $extra_seat_tour_time_fare->setRequired(true);
        $extra_seat_tour_time_fare->setDescription(p__('cabride', 'Set to 0 to disable extra seat fares'));

        $this->groupElements('group_tour', [
            'tour_base_fare',
            'tour_time_fare',
            'extra_seat_tour_base_fare',
            'extra_seat_tour_time_fare',
        ], p__('cabride', 'Tour fares'));

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
            "extra_seat_fare",
            "seat_distance_fare",
            "seat_time_fare",
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
            "extra_seat_fare",
            "seat_distance_fare",
            "seat_time_fare",
        ];
        foreach ($els as $el) {
            $this->getElement($el)->setRequired(false);
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
