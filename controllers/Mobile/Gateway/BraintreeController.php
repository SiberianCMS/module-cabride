<?php

/**
 * Class Cabride_Mobile_Gateway_BraintreeController
 */
class Cabride_Mobile_Gateway_BraintreeController extends Application_Controller_Mobile_Default
{
    /**
     * Returns the client token
     */
    public function getClientTokenAction() 
    {
        try {
            $request = $this->getRequest();
            //$customerId = $request->getParam("customerId", null);
            $customerId = "259231223";

            $gateway = new \Braintree\Gateway([
                'environment' => 'sandbox',
                'merchantId' => 'nq4gvtm673h59q5s',
                'publicKey' => 'fy63g99w8qr7sdk8',
                'privateKey' => 'f447ac2acdb26212c15e4d6011c1fafd'
            ]);


            if ($customerId) {
                try {
                    $clientToken = $gateway->clientToken()->generate([
                        "customerId" => $customerId
                    ]);
                } catch (\Exception $e) {
                    // Nope? so create the customer
                    $result = $gateway->customer()->create([
                        'firstName' => 'John',
                        'lastName' => 'Smith',
                        'company' => 'Smith Co.',
                        'email' => 'john@smith.com',
                        'website' => 'www.smithco.com',
                        'fax' => '419-555-1234',
                        'phone' => '614-555-1234'
                    ]);

                    if(!$result->success) {
                        throw new \Siberian\Exception(__("Unable to create a new client!"));

                    }

                    $customerId = $result->customer->id;
                    $clientToken = $gateway->clientToken()->generate([
                        "customerId" => $customerId
                    ]);
                }
            } else {
                // Nope? so create the customer
                $result = $gateway->customer()->create([
                    'firstName' => 'John',
                    'lastName' => 'Smith',
                    'company' => 'Smith Co.',
                    'email' => 'john@smith.com',
                    'website' => 'www.smithco.com',
                    'fax' => '419-555-1234',
                    'phone' => '614-555-1234'
                ]);

                if(!$result->success) {
                    throw new \Siberian\Exception(__("Unable to create a new client!"));

                }

                $customerId = $result->customer->id;
                $clientToken = $gateway->clientToken()->generate([
                    "customerId" => $customerId
                ]);
            }

            $payload = [
                'success' => true,
                'customerId' => $customerId,
                'token' => $clientToken,
                'message' => __('Success'),
            ];
        } catch (\Exception $e) {
            $payload = [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
        
        $this->_sendJson($payload);
    }

    public function validateTransactionAction ()
    {
        try {
            $request = $this->getRequest();
            $nonceFromTheClient = $request->getParam("nonce", null);

            $gateway = new \Braintree\Gateway([
                'environment' => 'sandbox',
                'merchantId' => 'nq4gvtm673h59q5s',
                'publicKey' => 'fy63g99w8qr7sdk8',
                'privateKey' => 'f447ac2acdb26212c15e4d6011c1fafd'
            ]);

            $result = $gateway->transaction()->sale([
                'amount' => '42.00',
                'paymentMethodNonce' => $nonceFromTheClient,
                'options' => [
                    'submitForSettlement' => true
                ]
            ]);

            $payload = [
                'success' => true,
                'result' => $result,
                'message' => __('Success'),
            ];
        } catch (\Exception $e) {
            $payload = [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }

        $this->_sendJson($payload);

    }
}
