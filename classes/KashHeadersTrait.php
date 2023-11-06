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
            $this->translator = $this->context->getTranslator();
        }

        $id_cart = $_SERVER['HTTP_KASH_CART_ID'] ?? null;
        if(is_null($id_cart)){
            $id_cart = (isset($_GET['Kash-Cart-Id'])? $_GET['Kash-Cart-Id']:null);
        }
        $cart = new \Cart($id_cart);
        $this->context->cart = $cart;
        $this->context->cookie->id_cart = $cart->id;
    }
}