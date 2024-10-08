<?php

use Cabride\Controller\Dashboard as Dashboard;
use Cabride\Model\Driver as Driver;
use Cabride\Form\Driver as FormDriver;
use Cabride\Form\Driver\Delete as DriverDelete;
use Siberian_Google_Geocoding as Geocoding;
use Siberian\Exception;

/**
 * Class Cabride_DriverController
 */
class Cabride_DriverController extends Dashboard
{

    /**
     * @throws Zend_Exception
     * @throws Zend_Form_Exception
     */
    public function loadformAction()
    {
        $driver_id = $this->getRequest()->getParam("driver_id");

        $Driver = new Driver();
        $Driver->find($driver_id);
        if ($Driver->getId()) {
            $form = new FormDriver();

            $form->populate($Driver->getData());
            $form->setValueId($this->getCurrentOptionValue()->getId());
            $form->removeNav("nav-cabride-driver");
            $form->addNav("edit-nav-cabride-driver", "Save", false);
            $form->setDriverId($Driver->getId());

            $payload = [
                'success' => true,
                'form' => $form->render(),
                'message' => p__("cabride", 'Success.'),
            ];
        } else {
            // Do whatever you need when form is not valid!
            $payload = [
                'error' => true,
                'message' => p__("cabride", 'The Driver you are trying to edit does not exists.'),
            ];
        }

        $this->_sendJson($payload);
    }

    public function editAction ()
    {
        try {
            $request = $this->getRequest();
            $this->view->edit = false;
            $driverId = $request->getParam("driver_id", null);
            if ($driverId) {
                $driver = (new Driver())
                    ->find($driverId);

                if ($driver->getId()) {
                    $this->view->driver = $driver;
                    $this->view->edit = true;
                }
            }
        } catch (\Exception $e) {
            // Create
        }

        parent::editAction();
    }

    /**
     * @throws Zend_Exception
     * @throws Zend_Form_Exception
     */
    public function editpostAction()
    {
        try {
            $application = $this->getApplication();
            $values = $this->getRequest()->getPost();

            $form = new FormDriver();
            $form->loadVehicles($values["value_id"]);
            if ($form->isValid($values)) {
                /** Do whatever you need when form is valid */

                // Geocoding base address
                $position = Geocoding::getLatLng(
                    ["address" => $values["base_address"]],
                    $application->getGooglemapsKey());

                if (empty($position[0]) || empty($position[1])) {
                    throw new Exception(p__("cabride", "Invalid address!"));
                }

                $driver = new Driver();
                $driver->addData($values);
                $driver->save();

                $payload = [
                    "success" => true,
                    "message" => p__("cabride", "Success."),
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
            /** Do whatever you need when form is not valid */
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

        $form = new DriverDelete();
        if ($form->isValid($values)) {
            $Driver = new Driver();
            $Driver->find($values["driver_id"]);
            $Driver->delete();

            $payload = [
                "success" => true,
                "message" => p__("cabride", "Driver successfully deleted."),
            ];
        } else {
            $payload = [
                "error" => 1,
                "message" => $form->getTextErrors(),
                "errors" => $form->getTextErrors(true),
            ];
        }

        $this->_sendJson($payload);
    }

}