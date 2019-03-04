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

        service.card = null;
        service.addEditCard = function () {
            service
            .init()
            .then(function () {
                switch (service.settings.paymentProvider) {
                    case "stripe":
                        service.addEditCardStripe();
                        break;
                    case "braintree":
                        break;
                    case "twocheckout":
                        break;
                }
            });
        };

        service.addEditCardStripe = function () {
            service.stripe = Stripe(service.settings.stripePublicKey);
            var elements = service.stripe.elements();
            var style = {
                base: {
                    color: "#32325d",
                    fontFamily: "'Helvetica Neue', Helvetica, sans-serif",
                    fontSmoothing: "antialiased",
                    fontSize: "16px",
                    "::placeholder": {
                        color: "#aab7c4"
                    }
                },
                invalid: {
                    color: "#fa755a",
                    iconColor: "#fa755a"
                }
            };
            service.card = elements.create("card", {style: style});
            var cardElement = document.getElementById("card-element");
            var saveElement = document.getElementById("save-element");
            var displayError = document.getElementById("card-errors");
            var displayErrorParent = document.getElementById("card-errors-parent");

            saveElement.setAttribute("disabled", "disabled");

            service.card.addEventListener("change", function(event) {
                if (event.error) {
                    displayErrorParent.classList.remove("ng-hide");
                    displayError.textContent = event.error.message;
                    saveElement.setAttribute("disabled", "disabled");
                } else {
                    displayErrorParent.classList.add("ng-hide");
                    displayError.textContent = "";
                    saveElement.removeAttribute("disabled");
                }
            });

            service.card.mount(cardElement);
        };

        service.saveCard = function () {
            switch (service.settings.paymentProvider) {
                case "stripe":
                    return service.createStripeToken();
                case "braintree":
                    break;
                case "twocheckout":
                    break;
            }

            return $q.reject("Invalid payment method!");
        };

        service.createStripeToken = function () {
            var deferred = $q.defer();

            try {
                var displayError = document.getElementById("card-errors");
                var displayErrorParent = document.getElementById("card-errors-parent");

                service.stripe.createToken(service.card).then(function(result) {
                    if (result.error) {
                        // Inform the customer that there was an error.
                        displayErrorParent.classList.remove("ng-hide");
                        displayError.textContent = result.error.message;

                        deferred.reject(result.error.message);
                    } else {
                        // Send the token to your server.
                        displayErrorParent.classList.add("ng-hide");
                        $injector.get("Cabride")
                        .saveCard(result.token, "stripe")
                        .then(function (payload) {

                            // Clear on success!
                            service.card.clear();

                            deferred.resolve(payload);
                        }, function (error) {
                            deferred.reject(error.message);
                        });
                    }
                });
            } catch (e) {
                deferred.reject(e.message);
            }

            return deferred.promise;
        };

        return service;
    });
