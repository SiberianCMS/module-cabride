<?php

namespace Cabride\Model;

use Core\Model\Base;

/**
 * Class Translation
 * @package Cabride\Model
 */
class Translation extends Base
{
    /**
     * @var string
     */
    protected $_db_table = Db\Table\Translation::class;

    /**
     * @param $payload
     * @return mixed
     * @throws \Zend_Exception
     */
    public static function overrideApp ($payload)
    {
        $appId = $payload['application']->getId();
        $currentLanguage = $payload['currentLanguage'];

        $dbTranslations = (new self())->findAll([
            'app_id = ?' => $appId,
            'locale = ?' => $currentLanguage
        ]);
        foreach ($dbTranslations as $dbTranslation) {
            $_db_key = base64_decode($dbTranslation->getData('original'));
            $_db_value = trim(base64_decode($dbTranslation->getTranslation()));

            if (!empty($_db_value) &&
                array_key_exists($_db_key, $payload['translationBlock']['_context']['cabride'])) {
                $payload['translationBlock']['_context']['cabride'][$_db_key] = $_db_value;
            }
        }

        return $payload;
    }

    /**
     * @param $payload
     * @return mixed
     * @throws \Zend_Exception
     */
    public static function overrideEditor ($payload)
    {
        // This is 'per-app' translation, so we only override if we are inside app context!
        if (empty($payload['application'])) {
            return $payload;
        }
        $appId = $payload['application']->getId();
        $currentLanguage = $payload['currentLanguage'];

        $dbTranslations = (new self())->findAll([
            'app_id = ?' => $appId,
            'locale = ?' => $currentLanguage
        ]);
        $keyValuesTr = [];
        foreach ($dbTranslations as $dbTranslation) {
            $_db_key = base64_decode($dbTranslation->getData('original'));
            $_db_value = base64_decode($dbTranslation->getTranslation());
            $keyValuesTr[$_db_key] = $_db_value;
        }

        foreach ($payload['translations'] as $key => $translation) {
            $_tr_key = $translation->getOriginal();
            $_tr_context = $translation->getContext();

            if (!array_key_exists($_tr_key, $keyValuesTr)) {
                continue;
            }

            $_tr_translation = trim($keyValuesTr[$_tr_key]);
            if (!empty($_tr_translation) && $_tr_context === 'cabride') {
                $payload['translations'][$key]->setTranslation($_tr_translation);
                unset($keyValuesTr[$_tr_key]);
            }
        }

        // Create all missing translations (missing keys)
        $remains = array_filter($keyValuesTr);
        foreach ($remains as $_remain_key => $_remain_value) {
            $context_key = 'cabride' . $_remain_key;
            $_ms_translation = new \Gettext\Translation('cabride', $_remain_key);
            $_ms_translation->setTranslation($_remain_value);
            $payload['translations'][$context_key] = $_ms_translation;
        }

        return $payload;
    }
}
