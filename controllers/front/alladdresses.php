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

class BinshopsrestAlladdressesModuleFrontController extends AbstractAuthRESTController
{
    protected function processGetRequest()
    {
        $customer = $this->context->customer;
        Db::getInstance(_PS_USE_SQL_SLAVE_)->disableCache();
        $psdata = $customer->getSimpleAddresses($this->context->language->id);
        foreach ($psdata as &$address) {
            $addressObject = new Address($address['id']);
            if (!empty($addressObject->kash_photo)) {
                $addressObject->loadPhoto();
                $address['kash_photo_base64'] = $addressObject->kash_photo_base64;
                $address['kash_photo_thumbnail_base64'] = $addressObject->kash_photo_thumbnail_base64;
            }
        }
        unset($address);

        $this->ajaxRender(json_encode([
            'success' => true,
            'code' => 200,
            'psdata' => $psdata
        ]));
        die;
    }
}
