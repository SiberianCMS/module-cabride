<?php

/**
 * Class Cabride_Form_Driver
 */
class Cabride_Form_Driver extends Siberian_Form_Abstract
{
    /**
     * @throws Zend_Form_Exception
     */
    public function init()
    {
        parent::init();

        $this
            ->setAction(__path("/cabride/driver/editpost"))
            ->setAttrib("id", "form-cabride-driver");

        // Bind as a create form!
        self::addClass("create", $this);

        $this->addSimpleHidden("driver_id");
        $this->addSimpleHidden("value_id");

        // Builds the default form from schema!
        
        $customer_id = $this->addSimpleText("customer_id", p__("cabride", "Customer ID"));
        $customer_id->setRequired(true);

        $vehicle_id = $this->addSimpleSelect("vehicle_id", p__("cabride", "Vehicle type"));
        $vehicle_id->setRequired(true);

        $vehicle_model = $this->addSimpleText("vehicle_model", p__("cabride", "Vehicle model"));
        $vehicle_model->setRequired(true);

        $vehicle_license_plate = $this->addSimpleText("vehicle_license_plate", p__("cabride", "Vehicle license plate"));
        $vehicle_license_plate->setRequired(true);

        $driver_license = $this->addSimpleText("driver_license", p__("cabride", "Driver license"));
        $driver_license->setRequired(true);

        $driver_photo = $this->addSimpleText("driver_photo", p__("cabride", "Driver photo"));
        $driver_photo->setRequired(true);

        $base_address = $this->addSimpleTextarea("base_address", p__("cabride", "Base address"));
        $base_address->setRequired(true);

        $pickup_radius = $this->addSimpleText("pickup_radius", p__("cabride", "Pickup radius"));
        $pickup_radius->setRequired(true);

        $status = $this->addSimpleText("status", p__("cabride", "Status"));
        $status->setRequired(true);#

        $submit = $this->addSubmit("Save", "Save");
        $submit->addClass("pull-right");
    }

    /**
     * @param $driver_id
     */
    public function setDriverId($driver_id)
    {
        $this->getElement("driver_id")->setValue($driver_id)->setRequired(true);
    }

    /**
     * @param $valueId
     * @return $this
     * @throws Zend_Exception
     */
    public function loadVehicles($valueId)
    {
        $vehicleTypes = (new Cabride_Model_Vehicle())
            ->findAll(["value_id = ?" => $valueId]);

        $vehicles = [];
        foreach ($vehicleTypes as $vehicleType) {
            $value = $vehicleType->getVehicleId();
            $label = $vehicleType->getType();

            $vehicles[$value] = $label;
        }
        
        $this->getElement("vehicle_id")->addMultiOptions($vehicles);

        return $this;
    }
}
