/**
 * CabridePayment service
 */
angular.module('starter')
    .service('CabridePayment', function (Application, $injector, $pwaRequest, $q) {
        var service = {
            stripe: null,
            settings: null,
            gateways: {
                brainTree: {
                    isLoaded: false,
                },
                stripe: {
                    isLoaded: false,
                },
                twocheckout: {
                    isLoaded: false,
                }
            },
        };

        // https://github.com/Xtraball/Siberian/blob/master/ionic/www/js/controllers/mcommerce/sales/stripe.js
        service.init = function () {
            var deferred = $q.defer();

            try {
                service.settings = $injector.get("Cabride").settings;
                switch (service.settings.paymentProvider) {
                    case "braintree":
                        // @todo
                        if (!service.gateways.brainTree.isLoaded) {
                            Application
                                .loaded
                                .then(function() {
                                    var brainTree = document.createElement("script");
                                    brainTree.type = "text/javascript";
                                    brainTree.src = "https://js.braintreegateway.com/web/dropin/1.14.1/js/dropin.min.js";
                                    brainTree.onload = function () {
                                        deferred.resolve();
                                        service.gateways.brainTree.isLoaded = true;
                                    };
                                    document.body.appendChild(brainTree);
                                });
                        }
                        break;
                    case "stripe":
                        if (!service.gateways.stripe.isLoaded) {
                            Application
                                .loaded
                                .then(function() {
                                    if (typeof Stripe === "undefined") {
                                        var stripeJS = document.createElement("script");
                                        stripeJS.type = "text/javascript";
                                        stripeJS.src = "https://js.stripe.com/v3/";
                                        stripeJS.onload = function () {
                                            deferred.resolve();
                                            service.gateways.stripe.isLoaded = true;
                                        };
                                        document.body.appendChild(stripeJS);
                                    }
                                });
                        }
                        break;
                    case "twocheckout":
                        // @todo
                        if (!service.gateways.twocheckout.isLoaded) {

                        }
                        break;
                }
            } catch (e) {
                deferred.reject();
            }

            return deferred.promise;
        };

        service.addEditCard = function () {
            service
            .init()
            .then(function () {
                service.stripe = Stripe(service.settings.stripePublicKey);
                var elements = service.stripe.elements();
                var style = {
                    base: {
                        color: '#32325d',
                        fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
                        fontSmoothing: 'antialiased',
                        fontSize: '16px',
                        '::placeholder': {
                            color: '#aab7c4'
                        }
                    },
                    invalid: {
                        color: '#fa755a',
                        iconColor: '#fa755a'
                    }
                };
                var card = elements.create('card', {style: style});
                var cardElement = document.getElementById("card-element");
                card.mount(cardElement);
            });
        };

        return service;
    });
