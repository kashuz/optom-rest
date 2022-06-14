<?php

trait KashHeadersTrait
{
    protected function processKashHeaders()
    {
        $langIsoCode = $_SERVER['HTTP_KASH_LANG_ISO_CODE'] ?? null;
        if (
            $langIsoCode !== null
            && $this->context->language->iso_code !== $langIsoCode
            && ($id = \Language::getIdByIso($langIsoCode))
            && ($language = new \Language($id))->id
        ) {
            $this->context->language = $language;
            $this->context->locale = $language->locale;
        }
    }
}