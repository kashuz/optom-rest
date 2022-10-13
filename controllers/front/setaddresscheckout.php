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

use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;

class BinshopsrestSetaddresscheckoutModuleFrontController extends AbstractAuthRESTController
{
    protected function processPostRequest()
    {
        $_POST = json_decode(Tools::file_get_contents('php://input'), true);
        if (Tools::getValue('id_address')) {
            $address = new Address(Tools::getValue('id_address'));
            if (
                !$address->id
                || $address->id_customer != $this->context->cart->id_customer
            ) {
                $this->ajaxRender(json_encode([
                    'success' => false,
                    'code' => 500,
                    'psdata' => $this->trans("Failed to set checkout address.", [], 'Modules.Binshopsrest.Checkout')
                ]));
                die;
            }

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

            $id_address_new = Tools::getValue('id_address');

            $session->setIdAddressDelivery($id_address_new);
            $session->setIdAddressInvoice($id_address_new);

            // further, it is fix of unclear bug in Prestashop

            $sql = 'UPDATE `' . _DB_PREFIX_ . 'cart_product`
                SET `id_address_delivery` = ' . (int) $id_address_new . '
                WHERE  `id_cart` = ' . (int) $this->context->cart->id;
            Db::getInstance()->execute($sql);

            $sql = 'UPDATE `' . _DB_PREFIX_ . 'customization`
                SET `id_address_delivery` = ' . (int) $id_address_new . '
                WHERE  `id_cart` = ' . (int) $this->context->cart->id;
            Db::getInstance()->execute($sql);

        } else {
            $this->ajaxRender(json_encode([
                'success' => false,
                'code' => 301,
                'psdata' => $this->trans("id_address-required", [], 'Modules.Binshopsrest.Checkout')
            ]));
            die;
        }

        $this->ajaxRender(json_encode([
            'success' => true,
            'code' => 200,
            'psdata' => $this->trans("id address has been successfully set", [], 'Modules.Binshopsrest.Checkout')
        ]));
        die;
    }
}
