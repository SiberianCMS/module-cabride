<?php

/**
 * Class Cabride_Form_Cabride
 */
class Cabride_Form_Cabride extends Siberian_Form_Abstract
{
    /**
     * @throws Zend_Form_Exception
     */
    public function init()
    {
        parent::init();

        $this
            ->setAction(__path("/cabride/cabride/editpost"))
            ->setAttrib("id", "form-cabride");
        // Bind as a create form!
        self::addClass("create", $this);

        $cabride_id = $this->addSimpleHidden("cabride_id");

        // Builds the default form from schema!
        
        $this->addSimpleHidden("value_id", __("Value Id"));

        $distance_unit = $this->addSimpleSelect(
            "distance_unit",
            __("Distance Unit"),
            [
                "km" => __("Km"),
                "mi" => __("Miles"),
            ]);
        $distance_unit->setRequired(true);

        $search_timeout = $this->addSimpleNumber(
            "search_timeout",
            __("Search Timeout"), 60, 3600, true, 1);
        $search_timeout->setRequired(true);

        $search_radius = $this->addSimpleNumber(
            "search_radius",
            __("Search Radius"), 10, 5000, true, 1);
        $search_radius->setRequired(true);

        $accepted_payments = $this->addSimpleSelect(
            "accepted_payments",
            __("Accepted Payments"),
            [
                "credit-card" => __("Credit card"),
                "cash" => __("Cash"),
                "all" => __("Credit card & Cash"),
            ]);
        $accepted_payments->setRequired(true);

        $commissionType = $this->addSimpleSelect(
            "commission_type",
            __("Commission type"),
            [
                "disabled" => __("Disabled"),
                "fixed" => __("Fixed amount"),
                "percentage" => __("Percentage"),
            ]);
        $commissionType->setRequired(true);

        $commission = $this->addSimpleText("commission", __("Commission"));
        $commission->setRequired(true);

        $course_mode = $this->addSimpleSelect(
            "course_mode",
            __("Course Mode"),
            [
                "immediate" => __("Immediate"),
                "all" => __("Immediate & Scheduled"),
            ]);
        $course_mode->setRequired(true);

        $pricing_mode = $this->addSimpleSelect(
            "pricing_mode",
            __("Pricing Mode"),
            [
                "fixed" => __("Fixed by vehicle type (Admin)"),
                "driver" => __("Fixed by the drivers"),
            ]);
        $pricing_mode->setRequired(true);

        $driver_can_register = $this->addSimpleCheckbox("driver_can_register", __("Driver Can Register"));
        $driver_can_register->setRequired(true);#
    }

    /**
     * @param $cabride_id
     */
    public function setCabrideId($cabride_id)
    {
        $this->getElement("cabride_id")->setValue($cabride_id)->setRequired(true);
    }
}
