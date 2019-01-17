<?php

/**
 * Class Cabride_ApplicationController
 */
class Cabride_GatewayController extends Application_Controller_Default
{
    public function stripeAction()
    {
        \Stripe\Stripe::setApiKey("sk_test_RFkTq8bTK9K22Be5hctpLaKvo");

        $customers = \Stripe\Customer::all();

        echo "<pre>";
        print_r($customers);
        die;
    }

}