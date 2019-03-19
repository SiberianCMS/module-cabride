<?php

/**
 * Class Cabride_DashboardController
 */
class Cabride_DashboardController extends Application_Controller_Default
{
    /**
     *
     */
    public function indexAction()
    {
        //$this->loadPartials();
        $this->redirect("/cabride/dashboard/rides");
    }

    /**
     *
     */
    public function usersAction()
    {
        $this->loadPartials();
    }

    /**
     *
     */
    public function driversAction()
    {
        $this->loadPartials();
    }

    /**
     *
     */
    public function ridesAction()
    {
        $this->loadPartials();
    }

    /**
     *
     */
    public function paymentsAction()
    {
        $this->loadPartials();
    }

    /**
     *
     */
    public function vehicleTypesAction()
    {
        $this->loadPartials();
    }

    /**
     *
     */
    public function settingsAction()
    {
        $this->loadPartials();
    }
}