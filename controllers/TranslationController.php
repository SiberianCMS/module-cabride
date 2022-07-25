<?php

/**
 * Class Cabride_TranslationController
 */
class Cabride_TranslationController extends Application_Controller_Default
{
    /**
     * @throws Zend_Exception
     * @throws Zend_Form_Exception
     */
    public function saveAction()
    {
        try {
            $request = $this->getRequest();
            $application = $this->getApplication();
            $appId = $application->getId();
            $translations = $request->getParam('tr');

            $currentLanguage = 'na';
            foreach ($translations as $code => $_translations) {
                $currentLanguage = $code;
                foreach ($_translations as $key => $_translation) {
                    $original = base64_encode($key);
                    $value = base64_encode($_translation['value']);

                    $dbTr = new \Cabride\Model\Translation();
                    $dbTr->find([
                        'app_id' => $appId,
                        'locale' => $code,
                        'original' => $original,
                    ]);
                    $dbTr
                        ->setLocale($code)
                        ->setAppId($appId)
                        ->setContext('cabride')
                        ->setOriginal($original)
                        ->setTranslation($value)
                        ->save();
                }
            }

            // Clear cache on save!
            $cacheId = 'v4_application_mobile_translation_findall_locale_' . $currentLanguage . '_appid_' . $appId;
            $this->cache->remove($cacheId);

            $payload = [
                'success' => true,
                'message' => p__("cabride", 'Saved.'),
            ];
        } catch (\Exception $e) {
            $payload = [
                'error' => true,
                'message' => p__("cabride", $e->getMessage()),
            ];
        }

        $this->_sendJson($payload);
    }

}
