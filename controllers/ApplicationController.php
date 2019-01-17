<?php

/**
 * Class Cabride_ApplicationController
 */
class Cabride_ApplicationController extends Application_Controller_Default
{

    /**
     * Load form edit
     */
    public function loadformAction()
    {
        $cabride_id = $this->getRequest()->getParam("cabride_id");

        $Cabride = new Cabride_Model_Cabride();
        $Cabride->find($cabride_id);
        if ($Cabride->getId()) {
            $form = new Cabride_Form_Cabride();

            $form->populate($Cabride->getData());
            $form->setValueId($this->getCurrentOptionValue()->getId());
            $form->removeNav("nav-cabride");
            $form->addNav("edit-nav-cabride", "Save", false);
            $form->setCabrideId($Cabride->getId());

            $payload = [
                'success' => true,
                'form' => $form->render(),
                'message' => __('Success.'),
            ];
        } else {
            // Do whatever you need when form is not valid!
            $payload = [
                'error' => true,
                'message' => __('The Cabride you are trying to edit doesn\'t exists.'),
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     * Create/Edit Cabride
     *
     * @throws exception
     */
    public function editpostAction()
    {
        $values = $this->getRequest()->getPost();

        $form = new Cabride_Form_Cabride();

        // Removes the require on commission if disabled!
        if ($values["commission_type"] === "disabled") {
            $form->getElement("commission")->setRequired(false);
        }

        if ($form->isValid($values)) {
            $cabride = new Cabride_Model_Cabride();
            $cabride->addData($values);
            $cabride->save();

            $payload = [
                "success" => true,
                "message" => __("Success"),
            ];
        } else {
            /** Do whatever you need when form is not valid */
            $payload = [
                "error" => true,
                "message" => $form->getTextErrors(),
                "errors" => $form->getTextErrors(true),
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     * Delete Cabride
     */
    public function deletepostAction()
    {
        $values = $this->getRequest()->getPost();

        $form = new Cabride_Form_Cabride_Delete();
        if ($form->isValid($values)) {
            $Cabride = new Cabride_Model_Cabride();
            $Cabride->find($values["cabride_id"]);
            $Cabride->delete();

            $payload = [
                'success' => true,
                'success_message' => __('Cabride successfully deleted.'),
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