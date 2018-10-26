<?php

/**
 * Class Cabride_PaymentController
 */
class Cabride_PaymentController extends Application_Controller_Default
{

    /**
     * Load form edit
     */
    public function loadformAction()
    {
        $payment_id = $this->getRequest()->getParam("payment_id");

        $Payment = new Cabride_Model_Payment();
        $Payment->find($payment_id);
        if ($Payment->getId()) {
            $form = new Cabride_Form_Payment();

            $form->populate($Payment->getData());
            $form->setValueId($this->getCurrentOptionValue()->getId());
            $form->removeNav("nav-cabride-payment");
            $form->addNav("edit-nav-cabride-payment", "Save", false);
            $form->setPaymentId($Payment->getId());

            $payload = [
                'success' => true,
                'form' => $form->render(),
                'message' => __('Success.'),
            ];
        } else {
            // Do whatever you need when form is not valid!
            $payload = [
                'error' => true,
                'message' => __('The Payment you are trying to edit doesn\'t exists.'),
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     * Create/Edit Payment
     *
     * @throws exception
     */
    public function editpostAction()
    {
        $values = $this->getRequest()->getPost();

        $form = new Cabride_Form_Payment();
        if ($form->isValid($values)) {
            /** Do whatever you need when form is valid */
            $Payment = new Cabride_Model_Payment();
            $Payment->addData($values);
            $Payment->save();

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
     * Delete Payment
     */
    public function deletepostAction()
    {
        $values = $this->getRequest()->getPost();

        $form = new Cabride_Form_Payment_Delete();
        if ($form->isValid($values)) {
            $Payment = new Cabride_Model_Payment();
            $Payment->find($values["payment_id"]);
            $Payment->delete();

            $payload = [
                'success' => true,
                'success_message' => __('Payment successfully deleted.'),
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