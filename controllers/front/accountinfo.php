<?php
/**
 * BINSHOPS | Best In Shops
 *
 * @author BINSHOPS | Best In Shops
 * @copyright BINSHOPS | Best In Shops
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * Best In Shops eCommerce Solutions Inc.
 *
 */

require_once dirname(__FILE__) . '/../AbstractAuthRESTController.php';

class BinshopsrestAccountinfoModuleFrontController extends AbstractAuthRESTController
{
    protected function processGetRequest()
    {
        $user = $this->context->customer;
        unset($user->secure_key);
        unset($user->passwd);
        unset($user->kash_mobile_token);
        unset($user->last_passwd_gen);
        unset($user->reset_password_token);
        unset($user->reset_password_validity);

        $availableCountries = Country::getCountries($this->context->language->id, true);
        $formatter = new CustomerAddressFormatter(
            $this->context->country,
            $this->getTranslator(),
            $availableCountries
        );
        $country = $formatter->getCountry();
        $user->states = State::getStatesByIdCountry($country->id);

        Db::getInstance()->disableCache();
        // due to unknown reason getRow() and getValue() do not work
        $result = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'cart c WHERE c.`id_customer` = ' . (int) $user->id . ' ORDER BY c.`id_cart` DESC LIMIT 1');
        $user->latest_cart_id = $result[0]['id_cart'] ?? null;

        $this->ajaxRender(json_encode([
            'code' => 200,
            'success' => true,
            'psdata' => $user
        ]));
        die;
    }
}
