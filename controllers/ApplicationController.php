<?php

use Cabride\Model\Cabride as Cabride;
use Cabride\Form\Cabride as FormCabride;
use Cabride\Form\Cabride\Delete as CabrideDelete;
use Cabride\Model\Stripe\Currency;
use Siberian_Google_Geocoding as Geocoding;
use Siberian\Exception;
use Siberian\Feature;
use Siberian\Json;

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

        $cabride = new Cabride();
        $cabride->find($cabride_id);
        if ($cabride->getId()) {
            $form = new FormCabride();

            $form->populate($cabride->getData());
            $form->setValueId($this->getCurrentOptionValue()->getId());
            $form->removeNav("nav-cabride");
            $form->addNav("edit-nav-cabride", "Save", false);
            $form->setCabrideId($cabride->getId());

            $payload = [
                'success' => true,
                'form' => $form->render(),
                'message' => p__("cabride", 'Success.'),
            ];
        } else {
            // Do whatever you need when form is not valid!
            $payload = [
                'error' => true,
                'message' => p__("cabride", 'The Cabride you are trying to edit does not exists.'),
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
            $form->populatePlaces($application->getId());

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

            $aGateways = [];
            foreach ($values as $key => $value) {
                if ((int) $value === 0) {
                    continue;
                }
                if (stripos($key, 'gateway_') === 0) {
                    $_gatewayKey = str_replace('gateway_', '', $key);

                    // Removes stripe if the currency is not supported***
                    if ($_gatewayKey === 'stripe' &&
                        !in_array($values['currency'], Currency::$supported, true)) {
                        continue;
                    }

                    // Otherwise continue!
                    $aGateways[] = $_gatewayKey;
                }
            }

            if (empty($aGateways)) {
                throw new Exception(p__('cabride','You must select at least one payment gateway!'));
            }

            $values['payment_gateways'] = implode(',', $aGateways);

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
                    "message" => p__("cabride", "Success"),
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
     *
     */
    public function deletepostAction()
    {
        try {
            $values = $this->getRequest()->getPost();

            $form = new CabrideDelete();
            if ($form->isValid($values)) {
                $Cabride = new Cabride();
                $Cabride->find($values["cabride_id"]);
                $Cabride->delete();

                $payload = [
                    "success" => true,
                    "message" => p__("cabride", "Cabride successfully deleted."),
                ];
            } else {
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

}
