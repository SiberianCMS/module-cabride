<?php

/**
 * Class Cabride_RequestController
 */
class Cabride_RequestController extends Application_Controller_Default
{

    /**
     * Load form edit
     */
    public function loadformAction()
    {
        $request_id = $this->getRequest()->getParam("request_id");

        $Request = new Cabride_Model_Request();
        $Request->find($request_id);
        if ($Request->getId()) {
            $form = new Cabride_Form_Request();

            $form->populate($Request->getData());
            $form->setValueId($this->getCurrentOptionValue()->getId());
            $form->removeNav("nav-cabride-request");
            $form->addNav("edit-nav-cabride-request", "Save", false);
            $form->setRequestId($Request->getId());

            $payload = [
                'success' => true,
                'form' => $form->render(),
                'message' => __('Success.'),
            ];
        } else {
            // Do whatever you need when form is not valid!
            $payload = [
                'error' => true,
                'message' => __('The Request you are trying to edit doesn\'t exists.'),
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     * Create/Edit Request
     *
     * @throws exception
     */
    public function editpostAction()
    {
        $values = $this->getRequest()->getPost();

        $form = new Cabride_Form_Request();
        if ($form->isValid($values)) {
            /** Do whatever you need when form is valid */
            $Request = new Cabride_Model_Request();
            $Request->addData($values);
            $Request->save();

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
     * Delete Request
     */
    public function deletepostAction()
    {
        $values = $this->getRequest()->getPost();

        $form = new Cabride_Form_Request_Delete();
        if ($form->isValid($values)) {
            $Request = new Cabride_Model_Request();
            $Request->find($values["request_id"]);
            $Request->delete();

            $payload = [
                'success' => true,
                'success_message' => __('Request successfully deleted.'),
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