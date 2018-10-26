<?php

/**
 * Class Cabride_Form_Cabride
 */
class Cabride_Form_Cabride extends Siberian_Form_Abstract
{
    /**
     * init wrapper
     */
    public function init()
    {
        parent::init();

        $this
            ->setAction(__path("/cabride/cabride/editpost"))
            ->setAttrib("id", "form-cabride")
            ->addNav("nav-cabride");

        // Bind as a create form!
        self::addClass("create", $this);

        $cabride_id = $this->addSimpleHidden("cabride_id");

        // Builds the default form from schema!
        
        $value_id = $this->addSimpleText("value_id", __("Value Id"));
        $value_id->setRequired(true);

        $distance_unit = $this->addSimpleText("distance_unit", __("Distance Unit"));
        $distance_unit->setRequired(true);

        $search_timeout = $this->addSimpleText("search_timeout", __("Search Timeout"));
        $search_timeout->setRequired(true);

        $search_radius = $this->addSimpleText("search_radius", __("Search Radius"));
        $search_radius->setRequired(true);

        $accepted_payments = $this->addSimpleTextarea("accepted_payments", __("Accepted Payments"));
        $accepted_payments->setRequired(true);

        $commission = $this->addSimpleText("commission", __("Commission"));
        $commission->setRequired(true);

        $course_mode = $this->addSimpleText("course_mode", __("Course Mode"));
        $course_mode->setRequired(true);

        $pricing_mode = $this->addSimpleText("pricing_mode", __("Pricing Mode"));
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
