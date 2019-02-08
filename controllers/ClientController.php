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
     * @throws Zend_Form_Exception
     */
    public function deletepostAction()
    {
        $values = $this->getRequest()->getPost();

        $form = new Cabride_Form_Client_Delete();

        if ($form->isValid($values)) {
            $client = new Cabride_Model_Client();
            $client->find($values["client_id"]);
            $client->delete();

            $payload = [
                "success" => true,
                "message" => p__("cabride", "Passenger deleted with success"),
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

    public function setAsDriverAction ()
    {
        try {
            $request = $this->getRequest();
            $clientId = $request->getParam("clientId", null);

            $client = (new Cabride_Model_Client())
                ->find([
                    "client_id" => $clientId
                ]);

            if (!$client->getId()) {
                throw new \Siberian\Exception(p__("cabride", "This passenger doesn't exists."));

            }

            $driver = new Cabride_Model_Driver();
            $driver
                ->setCustomerId($client->getCustomerId())
                ->setValueId($client->getValueId())
                ->save();

            $client->delete();

            $payload = [
                "success" => true,
                "message" => p__("cabride", "This user is now registered as a Driver."),
            ];
        } catch (\Exception $e) {
            $payload = [
                "error" => true,
                "message" => $e->getMessage(),
            ];
        }

        $this->_sendJson($payload);
    }

}