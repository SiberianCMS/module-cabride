<?php

use Cabride\Controller\Dashboard;
use Cabride\Model\Cabride;
use Cabride\Model\Vehicle;
use Cabride\Form\Vehicle as FormVehicle;
use Cabride\Form\Vehicle\Delete as VehicleDelete;

/**
 * Class Cabride_VehicleController
 */
class Cabride_VehicleController extends Dashboard
{
    /**
     * @throws \Zend_Exception
     * @throws \Zend_Form_Exception
     */
    public function loadformAction()
    {
        $vehicle_id = $this->getRequest()->getParam("vehicle_id");

        $vehicle = new Vehicle();
        $vehicle->find($vehicle_id);
        if ($vehicle->getId()) {
            $form = new FormVehicle();

            $form->populate($vehicle->getData());
            $form->setValueId($this->getCurrentOptionValue()->getId());
            $form->removeNav("nav-cabride-vehicle");
            $form->addNav("edit-nav-cabride-vehicle", "Save", false);
            $form->setVehicleId($vehicle->getId());

            $payload = [
                'success' => true,
                'form' => $form->render(),
                'message' => p__("cabride", 'Success.'),
            ];
        } else {
            // Do whatever you need when form is not valid!
            $payload = [
                'error' => true,
                'message' => p__("cabride", 'The Vehicle you are trying to edit does not exists.'),
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     *
     */
    public function editAction ()
    {
        try {
            $request = $this->getRequest();
            $this->view->edit = false;
            $vehicleId = $request->getParam("vehicle_id", null);
            if ($vehicleId) {
                $vehicle = (new Vehicle())
                    ->find($vehicleId);

                if ($vehicle->getId()) {
                    $this->view->vehicle = $vehicle;
                    $this->view->edit = true;
                }
            }
        } catch (\Exception $e) {
            // Create
        }

        parent::editAction();
    }

    /**
     * @throws Zend_Exception
     * @throws Zend_Form_Exception
     * @throws \Siberian\exception
     */
    public function editpostAction()
    {
        $values = $this->getRequest()->getPost();
        $optionValue = $this->getCurrentOptionValue();

        $cabride = (new Cabride())->find($optionValue->getId(), "value_id");

        $form = new FormVehicle();

        if ($cabride->getPricingMode() === "driver") {
            $form->disableFares();
        }

        if ($form->isValid($values)) {
            /** Do whatever you need when form is valid */
            $vehicle = new Vehicle();
            $vehicle->addData($values);

            Siberian\Feature::formImageForOption(
                $optionValue,
                $vehicle,
                $values,
                'icon',
                false
            );

            $vehicle->save();

            $payload = [
                'success' => true,
                'message' => p__("cabride", 'Success.'),
            ];
        } else {
            /** Do whatever you need when form is not valid */
            $payload = [
                'error' => true,
                'message' => $form->getTextErrors(),
                'errors' => $form->getTextErrors(true),
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     * @throws \Zend_Exception
     * @throws \Zend_Form_Exception
     */
    public function deletepostAction()
    {
        $values = $this->getRequest()->getPost();

        $form = new VehicleDelete();
        if ($form->isValid($values)) {
            $vehicle = new Vehicle();
            $vehicle->find($values["vehicle_id"]);
            $vehicle->delete();

            $payload = [
                "success" => true,
                "message" => p__("cabride", "Vehicle successfully deleted."),
            ];
        } else {
            $payload = [
                "error" => true,
                "message" => $form->getTextErrors(),
                "errors" => $form->getTextErrors(true),
            ];
        }

        $this->_sendJson($payload);
    }

}