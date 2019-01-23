/**
 * CabridePayment service
 */
angular.module('starter')
    .service('CabridePayment', function (Application, $pwaRequest) {
        var service = {
            gateways: {
                brainTree: {
                    isLoaded: false,
                },
                stripe: {
                    isLoaded: false,
                },
            },
        };

        service.init = function () {
            if (!service.gateways.brainTree.isLoaded) {
                Application
                    .loaded
                    .then(function() {
                        var brainTree = document.createElement('script');
                        brainTree.type = "text/javascript";
                        brainTree.src = "https://js.braintreegateway.com/web/dropin/1.14.1/js/dropin.min.js";
                        document.body.appendChild(brainTree);
                        service.gateways.brainTree.isLoaded = true;
                    });
            }
        };

        service.pay = function () {
            var clientToken = $pwaRequest.post("/cabride/mobile_gateway_braintree/get-client-token", {
                /**urlParams: {
                    customerId: this.customerId
                },*/
                cache: false
            });

            clientToken
                .then(function (success) {
                    var button = document.querySelector("#validate-payment");

                    braintree.dropin.create({
                        authorization: success.token,
                        container: '#dropin-container',
                        vaultManager: true,
                        paypal: {
                            flow: 'vault'
                        }
                    }, function (createErr, instance) {
                        button.addEventListener("click", function () {
                            instance.requestPaymentMethod(function (err, payload) {
                                // Submit payload.nonce to your server
                                console.log("clientToken err", err);
                                console.log("clientToken payload", payload);

                               var validateTransaction = $pwaRequest.post("/cabride/mobile_gateway_braintree/validate-transaction", {
                                   urlParams: {
                                       nonce: payload.nonce
                                   },
                                   cache: false
                                });

                                validateTransaction
                                    .then(function (success) {
                                        console.log("success", success);
                                        instance.close();
                                    }, function (error) {
                                        console.log("validateTransaction error", error);
                                    }).then(function () {

                                    });
                            });
                        });

                    });
                }, function (error) {
                    console.log("clientToken error", error);
                }).then(function () {

                });
        };

        return service;
    });
