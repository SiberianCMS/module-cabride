/**
 * CabridePayment service
 */
angular.module('starter')
    .service('CabridePayment', function (Application, $injector, $pwaRequest, $q, $ocLazyLoad) {
        var service = {
            stripe: null,
            settings: null,
        };

        service.init = function () {
            var config = [];

            service.settings = $injector.get("Cabride").settings;

            switch (service.settings.paymentProvider) {
                case "stripe":
                    config = [
                        "https://js.stripe.com/v3/"
                    ];
                    break;
            }

            return $ocLazyLoad.load(config);
        };

        service.card = null;
        service.addEditCard = function () {
            var promise = service.init();

            promise.then(function () {
                switch (service.settings.paymentProvider) {
                    case "stripe":
                        service.addEditCardStripe();
                        break;
                }
            });

            return promise;
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
            service.card = elements.create("card", {
                hidePostalCode: true,
                style: style
            });
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
