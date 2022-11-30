<?php
/**
 * BINSHOPS
 *
 * @author BINSHOPS
 * @copyright BINSHOPS
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * Best In Shops eCommerce Solutions Inc.
 *
 */

require_once dirname(__FILE__) . '/../AbstractCartRESTController.php';
require_once dirname(__FILE__) . '/../../classes/KashProductDiscountsTrait.php';

use PrestaShop\PrestaShop\Adapter\Presenter\Cart\CartPresenter;

/**
 * This REST endpoint adds a product to cart
 */
class BinshopsrestCartModuleFrontController extends AbstractCartRESTController
{
    use KashProductDiscountsTrait;

    protected function processGetRequest()
    {
        $this->updateCart();

        if (Configuration::isCatalogMode() && Tools::getValue('action') === 'show') {
            $this->ajaxRender(json_encode([
                'code' => 200,
                'success' => true,
                'message' => $this->trans('Just show - catalog mode is enabled and the action is show', [], 'Modules.Binshopsrest.Cart'),
            ]));
            die;
        }

        if (!Tools::getValue('ajax')) {
            $this->checkCartProductsMinimalQuantities();
        }
        $presenter = new CartPresenter();
        $presented_cart = $presenter->present($this->context->cart, $shouldSeparateGifts = true);

        $this->context->cart->id_lang = $this->context->language->id;

        $products = $this->context->cart->getProducts(true);
        $link = Context::getContext()->link;

        foreach ($products as $key => $product) {
            $products[$key]['image_url'] = $link->getImageLink($product['link_rewrite'], $product['id_image'], Tools::getValue('image_size', ImageType::getFormattedName('small')));

            $products[$key]['attributes_array'] = $presented_cart['products'][$key]['attributes'];

            $products[$key]['formatted_price'] = $presented_cart['products'][$key]['price'];
            $products[$key]['formatted_total'] = $presented_cart['products'][$key]['total'];
            $products[$key]['formatted_price_amount'] = $presented_cart['products'][$key]['price_amount'];
            $products[$key]['formatted_price_tax_exc'] = $presented_cart['products'][$key]['price_tax_exc'];
            $products[$key]['formatted_regular_price'] = $presented_cart['products'][$key]['regular_price'];
            $products[$key]['formatted_discount_to_display'] = $presented_cart['products'][$key]['discount_to_display'];
            $products[$key]['formatted_discount_amount_to_display'] = $presented_cart['products'][$key]['discount_amount_to_display'];
            $products[$key]['formatted_discount_type'] = $presented_cart['products'][$key]['discount_type'];
            $products[$key]['formatted_discount_percentage'] = $presented_cart['products'][$key]['discount_percentage'];
            $products[$key]['quantity_discounts'] = $this->getQuantityDiscounts($products[$key]['id_product'], $products[$key]['id_product_attribute']);
        }

        $presented_cart['products'] = $products;

        $this->ajaxRender(json_encode([
            'code' => 200,
            'success' => true,
            'message' => $this->trans('cart operation successfully done', [], 'Modules.Binshopsrest.Cart'),
            'psdata' => $presented_cart,
            'errors' => $this->errors
        ]));
        die;
    }

    protected function updateCart()
    {
        if (!$this->errors)
        {
            if (Tools::getIsset('add') || Tools::getIsset('update')) {
                $this->processChangeProductInCart();
            } elseif (Tools::getIsset('delete')) {
                $this->processDeleteProductInCart();
            } elseif (Tools::getIsset('deleteAll')) {
                foreach ($this->context->cart->getProducts(true) as $product) {
                    $this->id_product = $product['id_product'];
                    $this->id_product_attribute = $product['id_product_attribute'];
                    $this->processDeleteProductInCart();
                    unset($this->id_product);
                    unset($this->id_product_attribute);
                }
            } elseif (CartRule::isFeatureActive()) {
                if (Tools::getIsset('addDiscount')) {
                    if (!($code = trim(Tools::getValue('discount_name')))) {
                        $this->errors[] = $this->trans(
                            'You must enter a voucher code.',
                            [],
                            'Shop.Notifications.Error'
                        );
                    } elseif (!Validate::isCleanHtml($code)) {
                        $this->errors[] = $this->trans(
                            'The voucher code is invalid.',
                            [],
                            'Shop.Notifications.Error'
                        );
                    } else {
                        if (($cartRule = new CartRule(CartRule::getIdByCode($code)))
                            && Validate::isLoadedObject($cartRule)
                        ) {
                            if ($error = $cartRule->checkValidity($this->context, false, true)) {
                                $this->errors[] = $error;
                            } else {
                                $this->context->cart->addCartRule($cartRule->id);
                            }
                        } else {
                            $this->errors[] = $this->trans(
                                'This voucher does not exist.',
                                [],
                                'Shop.Notifications.Error'
                            );
                        }
                    }
                } elseif (($id_cart_rule = (int) Tools::getValue('deleteDiscount'))
                    && Validate::isUnsignedId($id_cart_rule)
                ) {
                    $this->context->cart->removeCartRule($id_cart_rule);
                    CartRule::autoAddToCart($this->context);
                }
            }
        } else {
            $this->ajaxRender(json_encode([
                'code' => 301,
                'success' => false,
                'message' => implode(' ', $this->errors),
            ]));
            die;
        }
    }
}
