<?php

/**
 * Class Cabride_Form_Driver
 */
class Cabride_Form_Driver extends Siberian_Form_Abstract
{
    /**
     * init wrapper
     */
    public function init()
    {
        parent::init();

        $this
            ->setAction(__path("/cabride/driver/editpost"))
            ->setAttrib("id", "form-cabride-driver")
            ->addNav("nav-cabride-driver");

        // Bind as a create form!
        self::addClass("create", $this);

        $driver_id = $this->addSimpleHidden("driver_id");

        // Builds the default form from schema!
        
        $customer_id = $this->addSimpleText("customer_id", __("Customer Id"));
        $customer_id->setRequired(true);

        $vehicle_id = $this->addSimpleText("vehicle_id", __("Vehicle Id"));
        $vehicle_id->setRequired(true);

        $vehicle_model = $this->addSimpleText("vehicle_model", __("Vehicle Model"));
        $vehicle_model->setRequired(true);

        $vehicle_license_plate = $this->addSimpleText("vehicle_license_plate", __("Vehicle License Plate"));
        $vehicle_license_plate->setRequired(true);

        $driver_license = $this->addSimpleText("driver_license", __("Driver License"));
        $driver_license->setRequired(true);

        $driver_photo = $this->addSimpleText("driver_photo", __("Driver Photo"));
        $driver_photo->setRequired(true);

        $base_address = $this->addSimpleTextarea("base_address", __("Base Address"));
        $base_address->setRequired(true);

        $pickup_radius = $this->addSimpleText("pickup_radius", __("Pickup Radius"));
        $pickup_radius->setRequired(true);

        $status = $this->addSimpleText("status", __("Status"));
        $status->setRequired(true);#
    }

    /**
     * @param $driver_id
     */
    public function setDriverId($driver_id)
    {
        $this->getElement("driver_id")->setValue($driver_id)->setRequired(true);
    }
}
