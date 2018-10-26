<?php

/**
 * Class Cabride_DriverController
 */
class Cabride_DriverController extends Application_Controller_Default
{

    /**
     * Load form edit
     */
    public function loadformAction()
    {
        $driver_id = $this->getRequest()->getParam("driver_id");

        $Driver = new Cabride_Model_Driver();
        $Driver->find($driver_id);
        if ($Driver->getId()) {
            $form = new Cabride_Form_Driver();

            $form->populate($Driver->getData());
            $form->setValueId($this->getCurrentOptionValue()->getId());
            $form->removeNav("nav-cabride-driver");
            $form->addNav("edit-nav-cabride-driver", "Save", false);
            $form->setDriverId($Driver->getId());

            $payload = [
                'success' => true,
                'form' => $form->render(),
                'message' => __('Success.'),
            ];
        } else {
            // Do whatever you need when form is not valid!
            $payload = [
                'error' => true,
                'message' => __('The Driver you are trying to edit doesn\'t exists.'),
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     * Create/Edit Driver
     *
     * @throws exception
     */
    public function editpostAction()
    {
        $values = $this->getRequest()->getPost();

        $form = new Cabride_Form_Driver();
        if ($form->isValid($values)) {
            /** Do whatever you need when form is valid */
            $Driver = new Cabride_Model_Driver();
            $Driver->addData($values);
            $Driver->save();

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
     * Delete Driver
     */
    public function deletepostAction()
    {
        $values = $this->getRequest()->getPost();

        $form = new Cabride_Form_Driver_Delete();
        if ($form->isValid($values)) {
            $Driver = new Cabride_Model_Driver();
            $Driver->find($values["driver_id"]);
            $Driver->delete();

            $payload = [
                'success' => true,
                'success_message' => __('Driver successfully deleted.'),
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