angular.module('starter')
.filter("cabrideStatusFilter", function() {
    return function(inputArray, statuses) {
        var result;

        result = [];
        inputArray.forEach(function(input) {
            var status = input.status;
            if (statuses.indexOf(status) >= 0) {
                result = result.concat([input]);
            }
        });

        return result;
    };
});