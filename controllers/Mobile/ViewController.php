<?php

use Cabride\Model\Cabride;
use Cabride\Model\Client;
use Cabride\Model\Driver;
use Cabride\Model\Field;
use Cabride\Model\Stripe\Currency;
use Siberian\Exception;
use Siberian\Json;
use Core\Model\Base;
use Cabride\Controller\Mobile as MobileController;

/**
 * Class Cabride_Mobile_ViewController
 */
class Cabride_Mobile_ViewController extends MobileController
{
    /**
     *
     */
    public function fetchSettingsAction()
    {
        try {
            // Fetch installation config file!
            $configFile = path('/app/local/modules/Cabride/resources/server/config.json');

            if (!file_exists($configFile)) {
                throw new Exception(__('The configuration files is missing!'));
            }

            $config = Json::decode(file_get_contents($configFile));
            $wssUrl = $config['wssHost'] . ':' . $config['port'] . '/cabride';

            // DB Config!
            $optionValue = $this->getCurrentOptionValue();
            $valueId = $optionValue->getId();
            $dbConfig = (new Cabride())
                ->find($valueId, 'value_id');

            $currency = Currency::getCurrency($dbConfig->getCurrency());

            $navBackground = $dbConfig->getNavBackground();
            if (!empty($navBackground)) {
                $navBackground = "/images/application{$navBackground}";
            }
            $driverPicture = $dbConfig->getDriverPicture();
            if (!empty($driverPicture)) {
                $driverPicture = "/images/application{$driverPicture}";
            }
            $passengerPicture = $dbConfig->getPassengerPicture();
            if (!empty($passengerPicture)) {
                $passengerPicture = "/images/application{$passengerPicture}";
            }

            // Custom form fields!
            $enableCustomForm = (boolean) $dbConfig->getEnableCustomForm();
            $customFormFields = [];
            if ($enableCustomForm) {
                // Fetching custom form fields
                /**
                 * @var $fields Field[]
                 */
                $fields = (new Field())->findAll(
                    [
                        'value_id = ?' => $valueId
                    ],
                    [
                        'position ASC'
                    ]
                );
                foreach ($fields as $field) {
                    $customField = [
                        'field_id' => (integer) $field->getFieldId(),
                        'label' => (string) $field->getLabel(),
                        'type' => (string) $field->getFieldType(),
                        'options' => (array) array_values($field->getFieldOptions()),
                        'min' => (float) $field->getNumberMin(),
                        'max' => (float) $field->getNumberMax(),
                        'step' => (float) $field->getNumberStep(),
                        'date_format' => (string) $field->getDateFormat(),
                        'datetime_format' => (string) $field->getDatetimeFormat(),
                        'is_required' => (boolean) $field->getIsRequired(),
                    ];
                    $defaultValue = (string) $field->getDefaultValue();
                    if (!empty($defaultValue)) {
                        switch ($field->getFieldType()) {
                            case 'number':
                                $defaultValue = (float) $defaultValue;
                                break;
                            case 'checkbox':
                                $defaultValue = (boolean) $defaultValue;
                                break;
                            default:
                                $defaultValue = (string) $defaultValue;
                        }
                        $customField['value'] = $defaultValue;
                    }
                    $customFormFields[] = $customField;
                }
            }

            // Check custom icon
            $customIcon = $dbConfig->getCustomIcon();
            if (empty($customIcon)) {
                $customIcon = false;
            }

            $payload = [
                'success' => true,
                'settings' => [
                    'wssUrl' => $wssUrl,
                    'pageTitle' => (string) $optionValue->getTabbarName(),
                    'distanceUnit' => (string) $dbConfig->getDistanceUnit(),
                    'searchTimeout' => (integer) $dbConfig->getSearchTimeout(),
                    'searchRadius' => (integer) $dbConfig->getSearchRadius(),
                    'acceptedPayments' => (string) $dbConfig->getAcceptedPayments(),
                    'paymentGateways' => explode(',', $dbConfig->getPaymentGateways()),
                    'courseMode' => (string) $dbConfig->getCourseMode(),
                    'pricingMode' => (string) $dbConfig->getPricingMode(),
                    'paymentProvider' => (string) $dbConfig->getPaymentProvider(),
                    'driverCanRegister' => (bool) $dbConfig->getDriverCanRegister(),
                    'enableCustomForm' => $enableCustomForm,
                    'enableTour' => (bool) $dbConfig->getEnableTour(),
                    'enableSeats' => (bool) $dbConfig->getEnableSeats(),
                    'seatsDefault' => (integer) $dbConfig->getSeatsDefault(),
                    'customFormFields' => $customFormFields,
                    'defaultLat' => (float) $dbConfig->getDefaultLat(),
                    'defaultLng' => (float) $dbConfig->getDefaultLng(),
                    'currency' => $currency,
                    'commission_type' => (string) $dbConfig->getCommissionType(),
                    'commission' => (float) $dbConfig->getCommission(),
                    'commission_fmt' => Base::_formatPrice($dbConfig->getCommission(), $currency),
                    'commission_fixed' => (float) $dbConfig->getCommissionFixed(),
                    'commission_fixed_fmt' => Base::_formatPrice($dbConfig->getCommissionFixed(), $currency),
                    'passengerPicture' => $passengerPicture,
                    'driverPicture' => $driverPicture,
                    'navBackground' => $navBackground,
                    'placesValueId' => (integer) $dbConfig->getPlacesValueId(),
                    'customIcon' => $customIcon,
                    'showPassengerPhoto' => (bool) $dbConfig->getShowPassengerPhoto(),
                    'showPassengerName' => (bool) $dbConfig->getShowPassengerName(),
                    'showPassengerPhone' => (bool) $dbConfig->getShowPassengerPhone(),
                    'canMakeOffer' => (bool) $dbConfig->getCanMakeOffer(),
                ]
            ];

        } catch (\Exception $e) {
            $payload = [
                'error' => true,
                'message' => $e->getMessage()
            ];
        }

        $this->_sendJson($payload);
    }

    public function fetchUserAction ()
    {
        try {
            $request = $this->getRequest();
            $valueId = $this->getCurrentOptionValue()->getId();
            $session = $this->getSession();
            $customer = $session->getCustomer();
            $customerId = $session->getCustomerId();

            // Try to sync/fetch mobile numbers!
            $mobileCustomer = trim($customer->getMobile());

            // First search in drivers!
            $driver = (new Driver())
                ->find([
                    'customer_id' => $customerId,
                    'value_id' => $valueId,
                ]);

            $user = null;
            if ($driver && $driver->getId()) {

                // Update data live!
                if (empty($mobileCustomer) &&
                    !empty($driver->getDriverPhone())) {
                    $customer
                        ->setMobile($driver->getDriverPhone())
                        ->save();
                }

                $user = [
                    'type' => 'driver',
                    'driverId' => (integer) $driver->getId(),
                    'isOnline' => (boolean) $driver->getIsOnline(),
                ];
            } else {
                $passenger = (new Client())
                    ->find([
                        'customer_id' => $customerId,
                        'value_id' => $valueId,
                    ]);

                if ($passenger && $passenger->getId()) {
                    // fetch saved cards if applies
                    // @todo

                    // Update mobile live!
                    if (empty($mobileCustomer) &&
                        !empty($passenger->getMobile())) {
                        $customer
                            ->setMobile($passenger->getMobile())
                            ->save();
                    }

                    $user = [
                        'clientId' => (integer) $passenger->getId(),
                        'type' => 'passenger',
                    ];
                } else {
                    $user = [
                        'clientId' => (integer) $passenger->getId(),
                        'type' => 'new',
                    ];
                }
            }

            $payload = [
                'success' => true,
                'user' => $user,
            ];
        } catch (\Exception $e) {
            $payload = [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     *
     */
    public function updateUserAction ()
    {
        try {
            $request = $this->getRequest();
            $valueId = $this->getCurrentOptionValue()->getId();
            $customerId = $this->getSession()->getCustomerId();
            $userType = $request->getParam('userType', 'passenger');

            switch ($userType) {
                case 'passenger':
                    $passenger = (new Client())
                        ->find([
                            'customer_id' => $customerId,
                            'value_id' => $valueId,
                        ]);

                    if ($passenger && !$passenger->getId()) {
                        $passenger
                            ->setCustomerId($customerId)
                            ->setValueId($valueId)
                            ->save();
                    }
                    break;
                case 'driver':
                    $driver = (new Driver())
                        ->find([
                            'customer_id' => $customerId,
                            'value_id' => $valueId,
                        ]);

                    if ($driver && !$driver->getId()) {
                        $driver
                            ->setCustomerId($customerId)
                            ->setValueId($valueId)
                            ->setStatus('active') // @todo adapt to settings
                            ->save();
                    }
                    $driver
                        ->setStatus('active') // @todo adapt to settings
                        ->save();

                    break;
            }

            $payload = [
                'success' => true,
                'message' => p__("cabride", 'Success'),
            ];
        } catch (\Exception $e) {
            $payload = [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     *
     */
    public function toggleOnlineAction ()
    {
        try {
            $request = $this->getRequest();
            $valueId = $this->getCurrentOptionValue()->getId();
            $customerId = $this->getSession()->getCustomerId();
            $isOnline = filter_var($request->getParam('isOnline', null), FILTER_VALIDATE_BOOLEAN);

            $driver = (new Driver())
                ->find([
                    'customer_id' => $customerId,
                    'value_id' => $valueId,
                ]);

            if ($driver && !$driver->getId()) {
                throw new Exception(p__('cabride',
                    'You are not registered as a driver! Please contact the App owner.'));
            }

            $profileErrors = $driver->getProfileErrors();
            if ($isOnline && count($profileErrors) > 0) {
                foreach ($profileErrors as &$profileError) {
                    $profileError = "- {$profileError}";
                }
                throw new Exception(p__('cabride', implode_polyfill('<br />', $profileErrors)));
            }

            $driver
                ->setIsOnline($isOnline)
                ->save();

            $payload = [
                'success' => true,
                'isOnline' => (boolean) $isOnline,
                'message' => p__("cabride", 'Success'),
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
