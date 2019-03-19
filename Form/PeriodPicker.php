<?php

namespace Cabride\Form;

use Siberian_Form_Abstract;

/**
 * Class PeriodPicker
 * @package Cabride\Form
 */
class PeriodPicker extends Siberian_Form_Abstract
{
    /**
     * @throws \Zend_Form_Exception
     */
    public function init()
    {
        $periodFrom = $this->addSimpleDatetimepicker(
            "period_from",
            __("From"),
            false,
            Siberian_Form_Abstract::DATEPICKER,
            "yy-mm-dd"
        );

        $periodTo = $this->addSimpleDatetimepicker(
            "period_to",
            __("To"),
            false,
            Siberian_Form_Abstract::DATEPICKER,
            "yy-mm-dd"
        );
    }
}
