<?php

use Cabride\Model\Payout;
use Cabride\Form\Payout as FormPayout;
use Cabride\Form\Payout\Delete as PayoutDelete;

/**
 * Class Cabride_PayoutController
 */
class Cabride_PayoutController extends Application_Controller_Default
{
    /**
     * @throws Zend_Exception
     * @throws Zend_Form_Exception
     */
    public function loadformAction()
    {
        $payout_id = $this->getRequest()->getParam("payout_id");

        $Payout = new Payout();
        $Payout->find($payout_id);
        if ($Payout->getId()) {
            $form = new FormPayout();

            $form->populate($Payout->getData());
            $form->setValueId($this->getCurrentOptionValue()->getId());
            $form->removeNav("nav-cabride-payout");
            $form->addNav("edit-nav-cabride-payout", "Save", false);
            $form->setPayoutId($Payout->getId());

            $payload = [
                'success' => true,
                'form' => $form->render(),
                'message' => __('Success.'),
            ];
        } else {
            // Do whatever you need when form is not valid!
            $payload = [
                'error' => true,
                'message' => __('The Payout you are trying to edit doesn\'t exists.'),
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     * Create/Edit Payout
     *
     * @throws exception
     */
    public function editpostAction()
    {
        $values = $this->getRequest()->getPost();

        $form = new FormPayout();
        if ($form->isValid($values)) {
            /** Do whatever you need when form is valid */
            $Payout = new Payout();
            $Payout->addData($values);
            $Payout->save();

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
     * @throws Zend_Exception
     * @throws Zend_Form_Exception
     */
    public function deletepostAction()
    {
        $values = $this->getRequest()->getPost();

        $form = new PayoutDelete();
        if ($form->isValid($values)) {
            $Payout = new Payout();
            $Payout->find($values["payout_id"]);
            $Payout->delete();

            $payload = [
                'success' => true,
                'success_message' => __('Payout successfully deleted.'),
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