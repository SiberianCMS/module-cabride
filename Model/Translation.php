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
        $translationBlock = $payload['translationBlock'];

        $dbTranslations = (new self())->findAll([
            'app_id = ?' => $appId,
            'locale = ?' => $currentLanguage
        ]);
        foreach ($dbTranslations as $dbTranslation) {
            $_db_key = base64_decode($dbTranslation->getData('original'));
            $_db_value = base64_decode($dbTranslation->getTranslation());

            if (array_key_exists($_db_key, $translationBlock['_context']['cabride'])) {
                $translationBlock['_context']['cabride'][$_db_key] = $_db_value;
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
            if (array_key_exists($_tr_key, $keyValuesTr) &&
                $_tr_context === 'cabride') {
                $payload['translations'][$key]->setTranslation($keyValuesTr[$_tr_key]);
            }
        }

        return $payload;
    }
}
