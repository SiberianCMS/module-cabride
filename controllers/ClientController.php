<?php

/**
 * Class Cabride_ClientController
 */
class Cabride_ClientController extends Application_Controller_Default
{

    /**
     * Load form edit
     */
    public function loadformAction()
    {
        $client_id = $this->getRequest()->getParam("client_id");

        $Client = new Cabride_Model_Client();
        $Client->find($client_id);
        if ($Client->getId()) {
            $form = new Cabride_Form_Client();

            $form->populate($Client->getData());
            $form->setValueId($this->getCurrentOptionValue()->getId());
            $form->removeNav("nav-cabride-client");
            $form->addNav("edit-nav-cabride-client", "Save", false);
            $form->setClientId($Client->getId());

            $payload = [
                'success' => true,
                'form' => $form->render(),
                'message' => __('Success.'),
            ];
        } else {
            // Do whatever you need when form is not valid!
            $payload = [
                'error' => true,
                'message' => __('The Client you are trying to edit doesn\'t exists.'),
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     * Create/Edit Client
     *
     * @throws exception
     */
    public function editpostAction()
    {
        $values = $this->getRequest()->getPost();

        $form = new Cabride_Form_Client();
        if ($form->isValid($values)) {
            /** Do whatever you need when form is valid */
            $Client = new Cabride_Model_Client();
            $Client->addData($values);
            $Client->save();

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
     * Delete Client
     */
    public function deletepostAction()
    {
        $values = $this->getRequest()->getPost();

        $form = new Cabride_Form_Client_Delete();
        if ($form->isValid($values)) {
            $Client = new Cabride_Model_Client();
            $Client->find($values["client_id"]);
            $Client->delete();

            $payload = [
                'success' => true,
                'success_message' => __('Client successfully deleted.'),
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