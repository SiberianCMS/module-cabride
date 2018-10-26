<?php

/**
 * Class Cabride_Form_Vehicle
 */
class Cabride_Form_Vehicle extends Siberian_Form_Abstract
{
    /**
     * init wrapper
     */
    public function init()
    {
        parent::init();

        $this
            ->setAction(__path("/cabride/vehicle/editpost"))
            ->setAttrib("id", "form-cabride-vehicle")
            ->addNav("nav-cabride-vehicle");

        // Bind as a create form!
        self::addClass("create", $this);

        $vehicle_id = $this->addSimpleHidden("vehicle_id");

        // Builds the default form from schema!
        
        $type = $this->addSimpleText("type", __("Type"));
        $type->setRequired(true);

        $base_fare = $this->addSimpleText("base_fare", __("Base Fare"));
        $base_fare->setRequired(true);

        $distance_fare = $this->addSimpleText("distance_fare", __("Distance Fare"));
        $distance_fare->setRequired(true);

        $time_fare = $this->addSimpleText("time_fare", __("Time Fare"));
        $time_fare->setRequired(true);

        $base_address = $this->addSimpleTextarea("base_address", __("Base Address"));
        $base_address->setRequired(true);

        $is_visible = $this->addSimpleCheckbox("is_visible", __("Is Visible"));
        $is_visible->setRequired(true);#
    }

    /**
     * @param $vehicle_id
     */
    public function setVehicleId($vehicle_id)
    {
        $this->getElement("vehicle_id")->setValue($vehicle_id)->setRequired(true);
    }
}
