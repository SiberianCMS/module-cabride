<?php

/**
 * Class Cabride_VehicleController
 */
class Cabride_VehicleController extends Cabride_Controller_Dashboard
{

    /**
     * Load form edit
     */
    public function loadformAction()
    {
        $vehicle_id = $this->getRequest()->getParam("vehicle_id");

        $Vehicle = new Cabride_Model_Vehicle();
        $Vehicle->find($vehicle_id);
        if ($Vehicle->getId()) {
            $form = new Cabride_Form_Vehicle();

            $form->populate($Vehicle->getData());
            $form->setValueId($this->getCurrentOptionValue()->getId());
            $form->removeNav("nav-cabride-vehicle");
            $form->addNav("edit-nav-cabride-vehicle", "Save", false);
            $form->setVehicleId($Vehicle->getId());

            $payload = [
                'success' => true,
                'form' => $form->render(),
                'message' => __('Success.'),
            ];
        } else {
            // Do whatever you need when form is not valid!
            $payload = [
                'error' => true,
                'message' => __('The Vehicle you are trying to edit doesn\'t exists.'),
            ];
        }

        $this->_sendJson($payload);
    }

    public function editAction ()
    {
        try {
            $request = $this->getRequest();
            $this->view->edit = false;
            $vehicleId = $request->getParam("vehicle_id", null);
            if ($vehicleId) {
                $vehicle = (new Cabride_Model_Vehicle())
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
     * Create/Edit Vehicle
     *
     * @throws exception
     */
    public function editpostAction()
    {
        $values = $this->getRequest()->getPost();

        $form = new Cabride_Form_Vehicle();
        if ($form->isValid($values)) {
            /** Do whatever you need when form is valid */
            $vehicle = new Cabride_Model_Vehicle();
            $vehicle->addData($values);
            $vehicle->save();

            $payload = [
                'success' => true,
                'message' => __('Success.'),
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
     * Delete Vehicle
     */
    public function deletepostAction()
    {
        $values = $this->getRequest()->getPost();

        $form = new Cabride_Form_Vehicle_Delete();
        if ($form->isValid($values)) {
            $vehicle = new Cabride_Model_Vehicle();
            $vehicle->find($values["vehicle_id"]);
            $vehicle->delete();

            $payload = [
                "success" => true,
                "message" => __("Vehicle successfully deleted."),
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