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
require_once _PS_MODULE_DIR_ . 'kash_checkout/classes/KashImageManager.php';

use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;

class BinshopsrestAddressModuleFrontController extends AbstractAuthRESTController
{
    protected function processGetRequest()
    {
        $address = new Address(
            Tools::getValue('id_address'),
            $this->context->language->id
        );
        $address->loadPhoto();

        $this->ajaxRender(json_encode([
            'success' => true,
            'code' => 200,
            'psdata' => $address
        ]));
        die;
    }

    protected function processPostRequest()
    {
        $_POST = json_decode(Tools::file_get_contents('php://input'), true);
        $validate_obj = $this->validatePost();

        if (!$validate_obj['valid']) {
            $this->ajaxRender(json_encode([
                'success' => false,
                'code' => 301,
                'psdata' => $validate_obj['errors']
            ]));
            die;
        }

        $availableCountries = Country::getCountries($this->context->language->id, true);
        $formatter = new CustomerAddressFormatter(
            $this->context->country,
            $this->getTranslator(),
            $availableCountries
        );

        $country = $formatter->getCountry();

        if (Tools::getValue('id_address')) {
            $msg = "Successfully updated address";
        } else {
            $msg = "Successfully added address";
        }

        $address = new Address(
            Tools::getValue('id_address'),
            $this->context->language->id
        );

        $deliveryOptionsFinder = new DeliveryOptionsFinder(
            $this->context,
            $this->getTranslator(),
            $this->objectPresenter,
            new PriceFormatter()
        );
        $session = new CheckoutSession(
            $this->context,
            $deliveryOptionsFinder
        );

        $address->firstname = $session->getCustomer()->firstname;
        $address->lastname = $session->getCustomer()->lastname;
        $address->phone = Validate::cleanKoreanPhoneNumber($session->getCustomer()->kash_phone);

        $address->id_country = $country->id;
        $address->id_state = Tools::getValue('id_state');
        $address->city = '(city is not set)';
        $address->address1 = Tools::getValue('address1');
        $address->alias = '(alias is not set)';

        if (!Tools::getValue('id_state')) {
            $address->id_state = State::getIdByIso(
                AddressFormat::STATE_MAINLAND_ISO_CODE,
                $country->id
            );
        }

        if (
            !empty(Tools::getValue('kash_photo_base64'))
            && !empty(Tools::getValue('kash_photo_name'))
        ) {
            KashImageManager::preuploadFromBase64('kash_photo', Tools::getValue('kash_photo_name'), Tools::getValue('kash_photo_base64'));
        }

        Hook::exec('actionSubmitCustomerAddressForm', ['address' => &$address]);

        $persister = new CustomerAddressPersister(
            $this->context->customer,
            $this->context->cart,
            Tools::getToken(true, $this->context)
        );

        try {
            $saved = $persister->save(
                $address,
                Tools::getToken(true, $this->context)
            );
        } catch (Exception $e) {
            $this->ajaxRender(json_encode([
                'success' => false,
                'code' => 302,
                'psdata' => $e->getMessage()
            ]));
            die;
        }

        if (!$saved) {
            $this->ajaxRender(json_encode([
                'success' => false,
                'code' => 302,
                'psdata' => "internal-server-error"
            ]));
            die;
        } else {
            $address->loadPhoto();
        }

        $this->ajaxRender(json_encode([
            'success' => true,
            'code' => 200,
            'psdata' => $address,
            'message' => $msg
        ]));
        die;
    }

    protected function processDeleteRequest()
    {
        $_POST = json_decode(Tools::file_get_contents('php://input'), true);
        Tools::getValue('id_address');

        $address = new Address(
            Tools::getValue('id_address'),
            $this->context->language->id
        );

        if ($address->id_customer != $this->context->customer->id) {
            $this->ajaxRender(json_encode([
                'success' => true,
                'code' => 301,
                'message' => $this->trans("Address is not available", [], 'Modules.Binshopsrest.Address')
            ]));
            die;
        }

        if ($address->id) {
            if (!$address->deleted){
                $address->deleted = true;

                $persister = new CustomerAddressPersister(
                    $this->context->customer,
                    $this->context->cart,
                    Tools::getToken(true, $this->context)
                );

                $saved = $persister->save(
                    $address,
                    Tools::getToken(true, $this->context)
                );
            }else{
                $this->ajaxRender(json_encode([
                    'success' => true,
                    'code' => 202,
                    'message' => $this->trans("Address was already deleted", [], 'Modules.Binshopsrest.Address')
                ]));
                die;
            }
        } else {
            $this->ajaxRender(json_encode([
                'success' => true,
                'code' => 301,
                'message' => $this->trans("Address is not available", [], 'Modules.Binshopsrest.Address')
            ]));
            die;
        }

        $this->ajaxRender(json_encode([
            'success' => true,
            'code' => 200,
            'psdata' => $saved,
            'message' => $this->trans("Address successfully deleted", [], 'Modules.Binshopsrest.Address')
        ]));
        die;
    }

    public function validatePost()
    {
        $psdata = array();
        $psdata['valid'] = true;
        $psdata['errors'] = array();

        if (!Tools::getValue('address1')) {
            $psdata['valid'] = false;
            $psdata['errors'][] = "address1-required";
        }

        return $psdata;
    }
}
