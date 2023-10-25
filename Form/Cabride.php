<?php

namespace Cabride\Form;

use PaymentMethod\Model\Gateway;
use Siberian_Form_Abstract;
use Cabride\Model\Stripe\Currency;

/**
 * Class Cabride
 * @package Cabride\Form
 */
class Cabride extends Siberian_Form_Abstract
{
    /**
     * @throws \Zend_Exception
     * @throws \Zend_Form_Exception
     * @throws \Zend_Validate_Exception
     */
    public function init()
    {
        parent::init();

        $this
            ->setAction(__path('/cabride/application/editpost'))
            ->setAttrib('id', 'form-cabride');

        // Bind as a create form!
        self::addClass('create', $this);

        $this->addSimpleHidden('cabride_id');

        // Builds the default form from schema!
        $this->addSimpleHidden('value_id');

        $adminEmails = $this->addSimpleText(
            'admin_emails',
            p__('cabride', 'Admin e-mails (coma separated)'));

        $this->groupElements('admin', ['admin_emails'], p__('cabride', 'Contact'));

        // All currencies
        $currency = $this->addSimpleSelect(
            'currency',
            p__('cabride', 'Currency'),
            Currency::getAllCurrencies());

        //$timezone = $this->addSimpleSelect(
        //    'timezone',
        //    p__('cabride', 'Timezone'),
        //    Timezone::getTranslated());

        $distance_unit = $this->addSimpleSelect(
            'distance_unit',
            p__('cabride', 'Distance unit'),
            [
                'km' => p__("cabride", 'Km'),
                'mi' => p__("cabride", 'Miles'),
            ]);

        $this->addSimpleText('center_map', p__('cabride', 'Center map address'));
        $this->addSimpleText('center_map', p__('cabride', 'Yolo'));
        $this->addSimpleText('center_map', p__('cabride', 'Yola'));

        $centerMapHint = p__('cabride', 'Leave blank to center map on user GPS position.');
        $html = <<<RAW
<div class="form-group sb-form-line">
    <label class="col-sm-3">&nbsp;</label>
    <div class="col-sm-7" 
         style="margin: 0 9px 0 7px;">
        <div class="alert alert-warning">
            $centerMapHint
        </div>
    </div>
</div>
RAW;

        $this->addSimpleHtml('center_map_hint', $html);

        $this->addSimpleSelect('places_value_id', p__('cabride', 'Load POI from a places feature.'));

        $this->groupElements('localization',
            ['currency', 'distance_unit', 'center_map', 'center_map_hint', 'places_value_id'],
            p__('cabride', 'Localization'));

        $search_timeout = $this->addSimpleNumber(
            'search_timeout',
            p__('cabride', 'Search timeout (seconds)'),
            60,
            3600,
            true,
            1);
        $search_timeout->setRequired(true);

        $search_radius = $this->addSimpleNumber(
            'search_radius',
            p__('cabride', 'Search radius (km/mi)'),
            1,
            5000,
            true,
            1);
        $search_radius->setRequired(true);

        $course_mode = $this->addSimpleSelect(
            'course_mode',
            p__('cabride', 'Course mode'),
            [
                'immediate' => p__('cabride', 'Immediate'),
                //"all" => p__("cabride", "Immediate & Scheduled"),
            ]);

        $this->groupElements('rides',
            ['search_timeout', 'search_radius', 'course_mode'],
            p__('cabride', 'Rides'));

        // Payment Gateways
        $gateways = Gateway::all();
        $gatewaysList = [];
        foreach ($gateways as $shortName => $gateway) {
            $label = $gateway['label'];
            try {
                $_gatewayInstance = new $gateway['class']();
                $isSetup = $_gatewayInstance->isSetup();
            } catch (\Exception $e) {
                $isSetup = false;
            }

            $shortCode = 'gateway_' . $shortName;
            $this->addSimpleCheckbox(
                $shortCode,
                p__('payment_method', $label));

            if (!$isSetup) {
                $this
                    ->getElement($shortCode)
                    ->setDescription(p__(
                        'payment_method',
                        'This payment method is not enabled and/or configured, go to <a href="/%s" target="_blank" style="text-decoration: underline; font-weight: bold;">Payment gateways > %s</a>',
                        $gateway['url'],
                        $label));
            }

            $gatewaysList[] = $shortCode;
        }

        $commissionType = $this->addSimpleSelect(
            'commission_type',
            p__('cabride', 'Commission type'),
            [
                'disabled' => p__('cabride', 'Disabled'),
                'fixed' => p__('cabride', 'Fixed amount'),
                'percentage' => p__('cabride', 'Percentage'),
                'mixed' => p__('cabride', 'Fixed amount + percentage'),
            ]);

        $commissionFixed = $this->addSimpleNumber('commission_fixed', p__('cabride', 'Commission (fixed)'), 0, null, true);
        $commissionFixed->setRequired(true);

        $commission = $this->addSimpleNumber('commission', p__('cabride', 'Commission (percentage)'), 0, 100, true);
        $commission->setRequired(true);

        $pricing_mode = $this->addSimpleSelect(
            'pricing_mode',
            p__('cabride', 'Pricing & number of seats mode'),
            [
                'fixed' => p__('cabride', 'Fixed by vehicle type (Admin)'),
                'driver' => p__('cabride', 'Fixed by the drivers'),
            ]);

        // Enable SEATS option
        $seats = $this->addSimpleCheckbox('enable_seats', p__('cabride', 'Enable seats pricing?'));
        $seatsText = p__('cabride', 'When enabled, users will be able to select the number of seats required.');
        $seatsHint = <<<RAW
<div class="form-group sb-form-line">
    <label class="col-sm-3">&nbsp;</label>
    <div class="col-sm-7" 
         style="margin: 0 9px 0 7px;">
        <div class="period_hint alert alert-warning">
            {$seatsText}
        </div>
    </div>
</div>
RAW;
        $this->addSimpleHtml('seats_hint', $seatsHint);

        $seatsDefault = $this->addSimpleNumber('seats_default', p__('cabride', 'Default number of seats'), 0, 100, true, 1);
        $seatsDefault->setValue(1);

        // Enable SEATS option
        $seatsDefaultText = p__('cabride', 'When set to 0, users will be required to fill the number of seats.');
        $seatsDefaultHint = <<<RAW
<div class="form-group sb-form-line">
    <label class="col-sm-3">&nbsp;</label>
    <div class="col-sm-7" 
         style="margin: 0 9px 0 7px;">
        <div class="period_hint alert alert-warning">
            {$seatsDefaultText}
        </div>
    </div>
</div>
RAW;
        $this->addSimpleHtml('seats_default_hint', $seatsDefaultHint);

        // Enable TOUR option
        $tour = $this->addSimpleCheckbox('enable_tour', p__('cabride', 'Enable tour option?'));
        $tourText = p__('cabride', 'When enabled, users will be able to book the driver for a given time.');
        $tourHint = <<<RAW
<div class="form-group sb-form-line">
    <label class="col-sm-3">&nbsp;</label>
    <div class="col-sm-7" 
         style="margin: 0 9px 0 7px;">
        <div class="period_hint alert alert-warning">{$tourText}</div>
    </div>
</div>
RAW;
        $this->addSimpleHtml('tour_hint', $tourHint);

        $canMakeOffer = $this->addSimpleCheckbox('can_make_offer', p__('cabride', 'Allow customers to offer a custom price?'));

        $canMakeOfferHint = p__('cabride', 'When enabled, customer will be able to make a custom offer for the rides, there is no min/max limit, they can even offer more than the estimated cost!');
        $offerHint = <<<RAW
<div class="form-group sb-form-line">
    <label class="col-sm-3">&nbsp;</label>
    <div class="col-sm-7" 
         style="margin: 0 9px 0 7px;">
        <div class="alert alert-warning">
            $canMakeOfferHint
        </div>
    </div>
</div>
RAW;

        $this->addSimpleHtml('can_make_offer_hint', $offerHint);

        $this->groupElements('payment_gateways', $gatewaysList, p__('cabride', 'Payment gateways'));

        $this->groupElements('payments',
            [
                'commission_type',
                'commission_fixed',
                'commission',
                'pricing_mode',
                'enable_seats',
                'seats_hint',
                'seats_default',
                'seats_default_hint',
                'enable_tour',
                'tour_hint',
                'can_make_offer',
                'can_make_offer_hint'
            ],
            p__('cabride', 'Payments'));

        // PAYOUTS CASH RETURNS
        $payoutPeriod = $this->addSimpleSelect(
            'payout_period',
            p__('cabride', 'Period'),
            [
                'disabled' => p__('cabride', 'Disabled'),
                'week' => p__('cabride', 'Weekly'),
                'month' => p__('cabride', 'Monthly'),
            ]);


        $disabledText = p__('cabride', 'Default behavior, you will manually generate payouts bulk CSV from the admin page.');
        $disabledHint = <<<RAW
<div class="form-group sb-form-line">
    <label class="col-sm-3">&nbsp;</label>
    <div class="col-sm-7" 
         style="margin: 0 9px 0 7px;">
        <div class="period_hint alert alert-warning">{$disabledText}</div>
    </div>
</div>
RAW;
        $weekText = p__('cabride', 'Payouts bulk CSV will be automatically generated every monday.');
        $weekHint = <<<RAW
<div class="form-group sb-form-line">
    <label class="col-sm-3">&nbsp;</label>
    <div class="col-sm-7" 
         style="margin: 0 9px 0 7px;">
        <div class="period_hint alert alert-warning">{$weekText}</div>
    </div>
</div>
RAW;
        $monthlyText = p__('cabride', 'Payouts bulk CSV will be automatically generated every month.');
        $monthlyHint = <<<RAW
<div class="form-group sb-form-line">
    <label class="col-sm-3">&nbsp;</label>
    <div class="col-sm-7" 
         style="margin: 0 9px 0 7px;">
        <div class="period_hint alert alert-warning">{$monthlyText}</div>
    </div>
</div>
RAW;

        $this->addSimpleHtml('payout_period_disabled', $disabledHint);
        $this->addSimpleHtml('payout_period_week', $weekHint);
        $this->addSimpleHtml('payout_period_month', $monthlyHint);

        $this->groupElements('payout_cash_return',
            ['payout_period', 'payout_period_disabled', 'payout_period_week', 'payout_period_month'],
            p__('cabride', 'Payouts & Cash return scheduler'));

        // DESIGN!
        $this->addSimpleImage(
            'nav_background',
            p__('cabride', 'Menu background'),
            p__('cabride', 'Menu background'),
            [
                'width' => 460,
                'height' => 340,
            ]
        );

        $this->addSimpleImage(
            'passenger_picture',
            p__('cabride', 'Passenger picture'),
            p__('cabride', 'Passenger picture'),
            [
                'width' => 512,
                'height' => 512,
            ]
        );

        $this->addSimpleImage(
            'driver_picture',
            p__('cabride', 'Driver picture'),
            p__('cabride', 'Driver picture'),
            [
                'width' => 512,
                'height' => 512,
            ]
        );

        $customIcon = $this->addSimpleTextarea(
            'custom_icon',
            p__('cabride', 'Custom icon on map, must be a valid SVG string.')
        );
        $customIcon->setNewDesign();

        $this->groupElements('design',
            [
                'nav_background_button',
                'passenger_picture_button',
                'driver_picture_button',
                'custom_icon'
            ],
            p__('cabride', 'Design'));


        // @todo work in progress
        // PASSENGER PRIVACY
        //$this->addSimpleCheckbox('show_passenger_photo', p__('cabride', 'Show passenger picture'));
        //$this->addSimpleCheckbox('show_passenger_name', p__('cabride', 'Show passenger name'));
        //$this->addSimpleCheckbox('show_passenger_phone', p__('cabride', 'Show passenger phone'));
//
        //$this->groupElements('privacy',
        //    ['show_passenger_photo', 'show_passenger_name', 'show_passenger_phone'],
        //    p__('cabride', 'Passenger privacy'));


        // MISC
        $this->addSimpleCheckbox('driver_can_register', p__('cabride', 'Driver can register'));

        $this->addSimpleCheckbox('enable_custom_form', p__('cabride', 'Enable custom form'));

        $this->groupElements('misc',
            ['driver_can_register', 'enable_custom_form'],
            p__('cabride', 'Misc'));

        $save = $this->addSubmit(p__('cabride', 'Save'), p__('cabride', 'Save'));
        $save->addClass('pull-right');
    }

    /**
     * @param $cabrideId
     */
    public function setCabrideId($cabrideId)
    {
        $this
            ->getElement('cabride_id')
            ->setValue($cabrideId)
            ->setRequired(true);
    }

    /**
     * @param $appId
     * @throws \Zend_Exception
     * @throws \Zend_Form_Exception
     */
    public function populatePlaces($appId)
    {
        $options = [];
        $options[] = p__('cabride', 'Select a place');

        $feature = (new \Application_Model_Option())->find('places', 'code');
        if ($feature && $feature->getId()) {
            $places = (new \Application_Model_Option_Value())
                ->findAll([
                    'aov.app_id = ?' => $appId,
                    'aov.option_id = ?' => $feature->getId()
                ]);

            foreach ($places as $place) {
                $options[$place->getValueId()] = '#' . $place->getValueId() . ' - ' . $place->getTabbarName();
            }
        }

        $this->getElement('places_value_id')->setMultiOptions($options);
    }
}
