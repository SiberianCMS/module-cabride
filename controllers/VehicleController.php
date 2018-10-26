<?php

/**
 * Class Cabride_VehicleController
 */
class Cabride_VehicleController extends Application_Controller_Default
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
            $Vehicle = new Cabride_Model_Vehicle();
            $Vehicle->addData($values);
            $Vehicle->save();

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
            $Vehicle = new Cabride_Model_Vehicle();
            $Vehicle->find($values["vehicle_id"]);
            $Vehicle->delete();

            $payload = [
                'success' => true,
                'success_message' => __('Vehicle successfully deleted.'),
                'message_loader' => 0,
                'message_button' => 0,
                'message_timeout' => 2
            ];
        } else {
            $payload = [
                'error' => 1,
                'message' => $form->getTextErrors(),
                'errors' => $form->getTextErrors(true),
            ];
        }

        $this->_sendJson($payload);
    }

}