<?php

use Cabride\Model\Payment;
use Cabride\Form\Payment as FormPayment;
use Cabride\Form\Payment\Delete as PaymentDelete;

/**
 * Class Cabride_PaymentController
 */
class Cabride_PaymentController extends Application_Controller_Default
{
    /**
     * @throws Zend_Exception
     * @throws Zend_Form_Exception
     */
    public function loadformAction()
    {
        $payment_id = $this->getRequest()->getParam("payment_id");

        $Payment = new Payment();
        $Payment->find($payment_id);
        if ($Payment->getId()) {
            $form = new FormPayment();

            $form->populate($Payment->getData());
            $form->setValueId($this->getCurrentOptionValue()->getId());
            $form->removeNav("nav-cabride-payment");
            $form->addNav("edit-nav-cabride-payment", "Save", false);
            $form->setPaymentId($Payment->getId());

            $payload = [
                'success' => true,
                'form' => $form->render(),
                'message' => p__("cabride", 'Success.'),
            ];
        } else {
            // Do whatever you need when form is not valid!
            $payload = [
                'error' => true,
                'message' => p__("cabride", 'The Payment you are trying to edit does not exists.'),
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     * @throws Zend_Exception
     * @throws Zend_Form_Exception
     */
    public function editpostAction()
    {
        $values = $this->getRequest()->getPost();

        $form = new FormPayment();
        if ($form->isValid($values)) {
            /** Do whatever you need when form is valid */
            $Payment = new Payment();
            $Payment->addData($values);
            $Payment->save();

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
     * @throws Zend_Exception
     * @throws Zend_Form_Exception
     */
    public function deletepostAction()
    {
        $values = $this->getRequest()->getPost();

        $form = new PaymentDelete();
        if ($form->isValid($values)) {
            $Payment = new Payment();
            $Payment->find($values["payment_id"]);
            $Payment->delete();

            $payload = [
                'success' => true,
                'success_message' => p__("cabride", 'Payment successfully deleted.'),
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