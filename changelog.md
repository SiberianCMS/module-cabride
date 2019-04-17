# VERSION 1.4.0 - APR. 17 2019

## FIXES

- Adds a new `timezone` setting to fix time shifting/expirations

---

# VERSION 1.3.0 - APR. 3 2019

## IMPROVEMENTS

- Adds map center field in settings page, leave blank = center on user location, fill with an address = center on the address.
- Adds a new commission mode Mixed you can set both a fixed & percentage fees.
- Prevents commission amount to be higher than the course total.
- Adds currents commission settings in the Vehicle information page (driver) if enabled.


## FIXES

- Fixes dashboard date sorting.

---

# VERSION 1.2.0 - MAR. 26 2019


##IMPROVEMENTS

- Adds a new page in Backoffice > Manage > Modules > Cabride with more settings for the websocket server.
    - Auth type: choose HTTP Basic or Bearer token, some admin panels do not support/allow HTTP Basic.
    - WebSocket port: use any other available open/port if 37000 is not available.



## FIXES

- Fixes payments & drivers pages not loading when settings were not saved.

---

# VERSION 1.1.0 - INITIAL RELEASE - MAR. 21 2019

## KEY FEATURES

- Revenue & payments dashboard
- Review all the rides and their statuses
- Manage your vehicle types
- Manage your cash return requests
- Export bulk CSV for your payouts, filter by period