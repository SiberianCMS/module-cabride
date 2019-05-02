<?php

use Cabride\Model\Cabride as Cabride;
use Cabride\Form\Cabride as FormCabride;
use Cabride\Form\Cabride\Delete as CabrideDelete;
use Siberian_Google_Geocoding as Geocoding;
use Siberian\Exception;
use Siberian\Feature;

/**
 * Class Cabride_ApplicationController
 */
class Cabride_ApplicationController extends Application_Controller_Default
{
    /**
     * @throws Zend_Exception
     * @throws Zend_Form_Exception
     */
    public function loadformAction()
    {
        $cabride_id = $this->getRequest()->getParam("cabride_id");

        $Cabride = new Cabride();
        $Cabride->find($cabride_id);
        if ($Cabride->getId()) {
            $form = new FormCabride();

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
     *
     */
    public function editpostAction()
    {
        try {
            $values = $this->getRequest()->getPost();
            $application = $this->getApplication();
            $optionValue = $this->getCurrentOptionValue();

            $form = new FormCabride();

            // Removes the require on commission if disabled!
            switch ($values["commission_type"]) {
                case "disabled":
                    $form->getElement("commission")->setRequired(false);
                    $form->getElement("commission_fixed")->setRequired(false);
                    break;
                case "fixed":
                    $form->getElement("commission")->setRequired(false);
                    break;
                case "percentage":
                    $form->getElement("commission_fixed")->setRequired(false);
                    break;
                case "mixed":
                    // Leave both required
                    break;
            }

            if ($form->isValid($values)) {
                $cabride = new Cabride();
                $cabride->addData($values);

                // Validating center map address
                if (!empty($values["center_map"])) {
                    $validate = Geocoding::validateAddress([
                        "refresh" => true,
                        "address" => $values["center_map"]
                    ], $application->getGooglemapsKey());

                    if ($validate === false) {
                        throw new Exception(p__("cabride","We are unable to validate your address!"));
                    }

                    $cabride
                        ->setDefaultLat($validate->getLatitude())
                        ->setDefaultLng($validate->getLongitude());
                } else {
                    $cabride
                        ->setCenterMap(null)
                        ->setDefaultLat(null)
                        ->setDefaultLng(null);
                }

                // Pictures
                Feature::formImageForOption($optionValue, $cabride, $values, "passenger_picture", true);
                Feature::formImageForOption($optionValue, $cabride, $values, "driver_picture", true);
                Feature::formImageForOption($optionValue, $cabride, $values, "nav_background", true);

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
        } catch (\Exception $e) {
            $payload = [
                "error" => true,
                "message" => $e->getMessage(),
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

        $form = new CabrideDelete();
        if ($form->isValid($values)) {
            $Cabride = new Cabride();
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