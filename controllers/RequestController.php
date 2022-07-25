<?php

use Cabride\Model\Request as Request;
use Cabride\Form\Request as FormRequest;
use Cabride\Form\Request\Delete as RequestDelete;

/**
 * Class Cabride_RequestController
 */
class Cabride_RequestController extends Application_Controller_Default
{
    /**
     * @throws Zend_Exception
     * @throws Zend_Form_Exception
     */
    public function loadformAction()
    {
        $request_id = $this->getRequest()->getParam("request_id");

        $request = new Request();
        $request->find($request_id);
        if ($request->getId()) {
            $form = new FormRequest();

            $form->populate($request->getData());
            $form->setValueId($this->getCurrentOptionValue()->getId());
            $form->removeNav("nav-cabride-request");
            $form->addNav("edit-nav-cabride-request", "Save", false);
            $form->setRequestId($request->getId());

            $payload = [
                'success' => true,
                'form' => $form->render(),
                'message' => p__("cabride", 'Success.'),
            ];
        } else {
            // Do whatever you need when form is not valid!
            $payload = [
                'error' => true,
                'message' => p__("cabride", 'The Request you are trying to edit does not exists.'),
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     * @throws \Zend_Exception
     * @throws \Zend_Form_Exception
     */
    public function editpostAction()
    {
        $values = $this->getRequest()->getPost();

        $form = new FormRequest();
        if ($form->isValid($values)) {
            /** Do whatever you need when form is valid */
            $Request = new Request();
            $Request->addData($values);
            $Request->save();

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

        $form = new RequestDelete();
        if ($form->isValid($values)) {
            $Request = new Request();
            $Request->find($values["request_id"]);
            $Request->delete();

            $payload = [
                'success' => true,
                'success_message' => p__("cabride", 'Request successfully deleted.'),
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