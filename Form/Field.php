<?php

namespace Cabride\Form;

use Siberian_Form_Abstract as FormAbstract;

/**
 * Class Field
 * @package Cabride\Form
 */
class Field extends FormAbstract
{
    /**
     * @var array
     */
    public static $types = [
        "divider" => "Divider (title)",
        "spacer" => "Spacer (white space)",
        "number" => "Number",
        //"select" => "Select",
        "checkbox" => "Checkbox",
        "password" => "Password",
        "text" => "Text",
        "textarea" => "Textarea",
        "date" => "Date",
        "datetime" => "Date & time",
    ];

    public static $dateFormats = [
        "MM/DD/YYYY" => "MM/DD/YYYY",
        "DD/MM/YYYY" => "DD/MM/YYYY",
        "MM DD YYYY" => "MM DD YYYY",
        "DD MM YYYY" => "DD MM YYYY",
        "YYYY-MM-DD" => "YYYY-MM-DD",
        "YYYY MM DD" => "YYYY MM DD",
    ];

    public static $datetimeFormats = [
        "MM/DD/YYYY HH:mm" => "MM/DD/YYYY HH:mm",
        "DD/MM/YYYY HH:mm" => "DD/MM/YYYY HH:mm",
        "MM DD YYYY HH:mm" => "MM DD YYYY HH:mm",
        "DD MM YYYY HH:mm" => "DD MM YYYY HH:mm",
        "YYYY-MM-DD HH:mm" => "YYYY-MM-DD HH:mm",
        "YYYY MM DD HH:mm" => "YYYY MM DD HH:mm",
    ];

    /**
     * @throws \Zend_Form_Exception
     * @throws \Zend_Validate_Exception
     */
    public function init()
    {
        parent::init();

        $this
            ->setAction(__path("/cabride/field/edit"))
            ->setAttrib("id", "form-edit-field");

        self::addClass("create", $this);

        $this->addSimpleHidden("field_id");
        $this->addSimpleHidden("value_id");
        $this->addSimpleHidden("position");

        $label = $this->addSimpleText("label", p__("cabride", "Label"));
        $label->setRequired(true);

        $fieldTypes = [];
        foreach (self::$types as $key => $label) {
            $fieldTypes[$key] = p__("cabride", $label);
        }

        $type = $this->addSimpleSelect("field_type", p__("cabride", "Type"), $fieldTypes);
        $type->setRequired(true);

        // Number
        $this->addSimpleNumber("number_min", p__("cabride", "Min. value"));
        $this->addSimpleNumber("number_max", p__("cabride", "Max. value"));
        $this->addSimpleNumber("number_step", p__("cabride", "Step"));

        $this->groupElements("group_number", ["number_min", "number_max", "number_step"], p__("cabride", "Number options"));

        // Date
        $this->addSimpleSelect("date_format", p__("cabride", "Date format"), self::$dateFormats);

        $this->groupElements("group_date", ["date_format"], p__("cabride", "Date options"));

        // Datetime
        $this->addSimpleSelect("datetime_format", p__("cabride", "Date & time format"), self::$datetimeFormats);

        $this->groupElements("group_datetime", ["datetime_format"], p__("cabride", "Date & time options"));

        // Default
        $this->addSimpleText("default_value", p__("cabride", "Default value"));

        // Required
        $this->addSimpleCheckbox("is_required", p__("cabride", "Required?"));

        $submit = $this->addSubmit(p__("cabride", "Save"));
        $submit->addClass("pull-right");
    }

    /**
     * @param $formId
     */
    public function binderField($formId)
    {
        $type = $this->getElement("field_type")->getValue();

        $js = <<<JS
<script type="text/javascript">
$(document).ready(function () {
    window.binderFormField("{$formId}");
    window.toggleGroups("{$formId}", "{$type}");
});
</script>
JS;

        $this->addMarkup($js);
    }

    /**
     * @param array $data
     * @return bool
     * @throws \Zend_Form_Exception
     */
    public function isValid($data)
    {
        switch ($data["field_type"]) {
            case "number":
                $this->getElement("number_min")->setRequired(true);
                $this->getElement("number_max")->setRequired(true);
                $this->getElement("number_step")->setRequired(true);
                break;
            case "date":
                $this->getElement("date_format")->setRequired(true);
                break;
            case "datetime":
                $this->getElement("datetime_format")->setRequired(true);
                break;
        }

        return parent::isValid($data);
    }
}