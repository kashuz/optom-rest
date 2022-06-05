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

require_once dirname(__FILE__) . '/../AbstractRESTController.php';

class BinshopsrestAlladdressesModuleFrontController extends AbstractRESTController
{
    protected function processGetRequest()
    {
        $customer = $this->context->customer;
        $psdata = $customer->getSimpleAddresses(
            $this->context->language->id,
            true // no cache
        );
        foreach ($psdata as &$address) {
            if (!empty($address['kash_photo'])) {
                $addressObject = new Address($address['id']);
                $addressObject->loadPhoto();
                $address['kash_photo_base64'] = $address->kash_photo_base64;
                $address['kash_photo_thumbnail_base64'] = $address->kash_photo_thumbnail_base64;
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
