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
class BinshopsrestCartv2ModuleFrontController extends AbstractCartRESTController
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
            $availableQuantity = StockAvailable::getQuantityAvailableByProduct($product['id_product'], $product['id_product_attribute']);
            $nbProductInCart = $product['cart_quantity'];
            $products[$key]['stock_quantity'] = $availableQuantity - $nbProductInCart;
            $products[$key]['quantity_available'] = (string) ($availableQuantity - $nbProductInCart);
        }
        if(Module::isEnabled('marketplace') && Module::isEnabled('mpcartordersplit')){
            $sellerWiseProducts = [];
            require_once _PS_MODULE_DIR_ . 'mpcartordersplit/classes/CarrierProductMap.php';
            $objCarrierProductMap = new CarrierProductMap();
            foreach ($products as $product) {
                $sellerDetail = $objCarrierProductMap->getSellerDetailByIdProd(
                    $product['id_product'],
                    $this->context->language->id
                );
                if ($sellerDetail) {
                    if (!isset($sellerWiseProducts[$sellerDetail['id_seller']])) {
                        $sellerWiseProducts[$sellerDetail['id_seller']] = [
                            'seller' => [
                                'id_seller' => $sellerDetail['id_seller'],
                                'shop_name' => $sellerDetail['shop_name'],
                                'shop_link' => $this->context->link->getModuleLink(
                                    'marketplace',
                                    'shopstore',
                                    [
                                        'mp_shop_name' => $sellerDetail['shop_link_rewrite'],
                                        'id_seller' => $sellerDetail['id_seller']
                                    ]
                                ),
                            ],
                            'products' => [],
                        ];
                    }
                    $sellerWiseProducts[$sellerDetail['id_seller']]['products'][] = $product;
                } else {
                    $idSeller = 0;
                    if (!isset($sellerWiseProducts[$idSeller])) {
                        $sellerWiseProducts[$idSeller] = [
                            'seller' => [
                                'id_seller' => $idSeller,
                                'shop_name' => 'Optom.App [Ansan]',
                                'shop_link' => $this->context->shop->getBaseURL(true, true),
                            ],
                            'products' => [],
                        ];
                    }
                    $sellerWiseProducts[$idSeller]['products'][] = $product;
                }
            }

            $productListBySeller = [];
            if($this->context->cart->id_carrier == 0){
                $selected_id_carrier = Carrier::getDefaultCarrierSelection(Carrier::getCarriers($this->context->language->id,true));
            }else{
                $selected_id_carrier = $this->context->cart->id_carrier;
            }

            $carrier = new Carrier($selected_id_carrier,$this->context->language->id);
            foreach ($products as $product) {
                $id_seller = $objCarrierProductMap->getSellerIdByIdProd($product['id_product']);
                $id_seller = $id_seller ? $id_seller : 0;
                $productListBySeller[$id_seller][] = $product;
            }
            $currency = new Currency($this->context->cart->id_currency,$this->context->cart->id_lang);
            foreach ($productListBySeller as $id_seller => $product_list) {
                $sellerWiseShippingCost = $this->context->cart->getMpPackageShippingCost(null,null,null, $product_list);
                $productTotalCost = $this->context->cart->getOrderTotal(true, Cart::ONLY_PRODUCTS, $product_list, null, false);
                if ($sellerWiseShippingCost !== false) {
                    if($sellerWiseShippingCost == 0){
                        $shippingValue = $this->trans('Free',[],'Modules.Mpcartordersplit.Mpcartordersplit',$this->context->language->iso_code);
                    }else{
                        $shippingValue = Context::getContext()->currentLocale->formatPrice($sellerWiseShippingCost,$currency->iso_code);
                    }
                    $sellerWiseProducts[$id_seller]['shipping'] =[
                        'amount'=>$sellerWiseShippingCost,
                        'value' => $shippingValue
                    ];

                    $sellerFreeShippingInfo = $this->sellerFreeShippingInfo($sellerWiseProducts[$id_seller]['products']);
                    if(!is_array($sellerFreeShippingInfo)){
                        $sellerWiseProducts[$id_seller][$id_carrier]['freeShippingInfo'] = [
                            'free_ship_remaining' => 0
                        ];
                    }else{
                        $sellerWiseProducts[$id_seller]['freeShippingInfo'] = $this->sellerFreeShippingInfo($sellerWiseProducts[$id_seller]['products']);
                    }
                    
                    $sellerWiseProducts[$id_seller]['subtotals']['products'] =[
                        'amount'=>$productTotalCost,
                        'value' => Context::getContext()->currentLocale->formatPrice($productTotalCost,$currency->iso_code)
                    ];
                    $sellerWiseProducts[$id_seller]['carrier'] = $carrier;
                }
            }

            $sellerWithProducts = [];
            foreach($sellerWiseProducts as $seller){
                $sellerWithProducts[] = $seller;
            }

            $presented_cart['products'] = $sellerWithProducts;
        }else{
            $presented_cart['products'] = $products;
        }
        

        $this->ajaxRender(json_encode([
            'code' => 200,
            'success' => true,
            'message' => $this->trans('cart operation successfully done', [], 'Modules.Binshopsrest.Cart'),
            'psdata' => $presented_cart,
            'errors' => $this->errors,
            'updateErrors' => $this->updateOperationError,
            'id_cart' => $this->context->cart->id
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

    protected function shouldAvailabilityErrorBeRaisedBeforeAdd($product, $qtyToCheck)
    {
        if (($this->id_product_attribute)) {
            return !Product::isAvailableWhenOutOfStock($product->out_of_stock)
                && !Attribute::checkAttributeQty($this->id_product_attribute, $qtyToCheck);
        } elseif (Product::isAvailableWhenOutOfStock($product->out_of_stock)) {
            return false;
        }

        // Check if this product is out-of-stock
        $availableProductQuantity = StockAvailable::getQuantityAvailableByProduct(
            $this->id_product,
            $this->id_product_attribute
        );
        if ($availableProductQuantity <= 0) {
            return true;
        }

        // Check if this product is out-of-stock after cart quantities have been removed from stock
        // Be aware that Product::getQuantity() returns the available quantity after decreasing products in cart
        $productQuantityAvailableAfterCartItemsHaveBeenRemovedFromStock = Product::getQuantity(
            $this->id_product,
            $this->id_product_attribute,
            null,
            $this->context->cart,
            $this->customization_id
        );
        return $productQuantityAvailableAfterCartItemsHaveBeenRemovedFromStock <= 0;
    }
    
    protected function processChangeProductInCart()
    {
        $mode = (Tools::getIsset('update') && $this->id_product) ? 'update' : 'add';
        $ErrorKey = ('update' === $mode) ? 'updateOperationError' : 'errors';

        if (Tools::getIsset('group')) {
            $this->id_product_attribute = (int) Product::getIdProductAttributeByIdAttributes(
                $this->id_product,
                Tools::getValue('group')
            );
        }

        if ($this->qty == 0) {
            $this->{$ErrorKey}[] = $this->trans(
                'Null quantity.',
                [],
                'Shop.Notifications.Error'
            );
        } elseif (!$this->id_product) {
            $this->{$ErrorKey}[] = $this->trans(
                'Product not found',
                [],
                'Shop.Notifications.Error'
            );
        }

        $product = new Product($this->id_product, true, $this->context->language->id);
        if (!$product->id || !$product->active || !$product->checkAccess($this->context->cart->id_customer)) {
            $this->{$ErrorKey}[] = $this->trans(
                'This product (%product%) is no longer available.',
                ['%product%' => $product->name],
                'Shop.Notifications.Error'
            );

            return;
        }

        if (!$this->id_product_attribute && $product->hasAttributes()) {
            $minimum_quantity = ($product->out_of_stock == 2)
                ? !Configuration::get('PS_ORDER_OUT_OF_STOCK')
                : !$product->out_of_stock;
            $this->id_product_attribute = Product::getDefaultAttribute($product->id, $minimum_quantity);
            // @todo do something better than a redirect admin !!
            if (!$this->id_product_attribute) {
                Tools::redirectAdmin($this->context->link->getProductLink($product));
            }
        }

        $qty_to_check = $this->qty;
        $cart_products = $this->context->cart->getProducts();

        if (is_array($cart_products)) {
            foreach ($cart_products as $cart_product) {
                if ($this->productInCartMatchesCriteria($cart_product)) {
                    $qty_to_check = $cart_product['cart_quantity'];

                    if (Tools::getValue('op', 'up') == 'down') {
                        $qty_to_check -= $this->qty;
                    } else {
                        $qty_to_check += $this->qty;
                    }

                    break;
                }
            }
        }

        // Check product quantity availability
        if ($this->shouldAvailabilityErrorBeRaisedBeforeAdd($product, $qty_to_check)) {
            $this->{$ErrorKey}[] = $this->trans(
                'The product is no longer available in this quantity.',
                [],
                'Shop.Notifications.Error'
            );
            if (Tools::getValue('op', 'up') != 'down' && $ErrorKey != 'errors'){
                $this->errors[] = $this->trans(
                    'The product is no longer available in this quantity.',
                    [],
                    'Shop.Notifications.Error'
                );
            }
        }

        // Check minimal_quantity
        if (!$this->id_product_attribute) {
            if ($qty_to_check < $product->minimal_quantity) {
                $this->errors[] = $this->trans(
                    'The minimum purchase order quantity for the product %product% is %quantity%.',
                    ['%product%' => $product->name, '%quantity%' => $product->minimal_quantity],
                    'Shop.Notifications.Error'
                );

                return;
            }
        } else {
            $combination = new Combination($this->id_product_attribute);
            if ($qty_to_check < $combination->minimal_quantity) {
                $this->errors[] = $this->trans(
                    'The minimum purchase order quantity for the product %product% is %quantity%.',
                    ['%product%' => $product->name, '%quantity%' => $combination->minimal_quantity],
                    'Shop.Notifications.Error'
                );

                return;
            }
        }

        // If no errors, process product addition
        if (!$this->errors) {
            // Add cart if no cart found
            if (!$this->context->cart->id) {
                if (Context::getContext()->cookie->id_guest) {
                    $guest = new Guest(Context::getContext()->cookie->id_guest);
                    $this->context->cart->mobile_theme = $guest->mobile_theme;
                }
                $this->context->cart->add();
                if ($this->context->cart->id) {
                    $this->context->cookie->id_cart = (int) $this->context->cart->id;
                }
            }

            // Check customizable fields

            if (!$product->hasAllRequiredCustomizableFields() && !$this->customization_id) {
                $this->{$ErrorKey}[] = $this->trans(
                    'Please fill in all of the required fields, and then save your customizations.',
                    [],
                    'Shop.Notifications.Error'
                );
            }

            if (!$this->errors) {
                $update_quantity = $this->context->cart->updateQty(
                    $this->qty,
                    $this->id_product,
                    $this->id_product_attribute,
                    $this->customization_id,
                    Tools::getValue('op', 'up'),
                    $this->id_address_delivery,
                    null,
                    true,
                    true
                );
                if ($update_quantity < 0) {
                    // If product has attribute, minimal quantity is set with minimal quantity of attribute
                    $minimal_quantity = ($this->id_product_attribute)
                        ? Attribute::getAttributeMinimalQty($this->id_product_attribute)
                        : $product->minimal_quantity;
                    $this->{$ErrorKey}[] = $this->trans(
                        'You must add %quantity% minimum quantity',
                        ['%quantity%' => $minimal_quantity],
                        'Shop.Notifications.Error'
                    );
                } elseif (!$update_quantity) {
                    $this->errors[] = $this->trans(
                        'You already have the maximum quantity available for this product.',
                        [],
                        'Shop.Notifications.Error'
                    );
                } elseif ($this->shouldAvailabilityErrorBeRaised($product, $qty_to_check)) {
                    // check quantity after cart quantity update
                    $this->{$ErrorKey}[] = $this->trans(
                        'The product is no longer available in this quantity.',
                        [],
                        'Shop.Notifications.Error'
                    );
                }
            }
        }

        $removed = CartRule::autoRemoveFromCart();
        CartRule::autoAddToCart();
    }

    public function sellerFreeShippingInfo($product_list){
        if (Configuration::get('iqitfdc_custom_status') && Configuration::get('custom_status_on')) {
            $free_ship_from = Tools::convertPrice(
                (float) Configuration::get('iqitfdc_custom_amount'),
                Currency::getCurrencyInstance((int) Context::getContext()->currency->id)
            );
        } else {
            $free_ship_from = Tools::convertPrice(
                (float) Configuration::get('PS_SHIPPING_FREE_PRICE'),
                Currency::getCurrencyInstance((int) Context::getContext()->currency->id)
            );
        }
        
        $free_ship_from_amount = Context::getContext()->currentLocale->formatPrice($free_ship_from,$this->context->currency->iso_code);
        $hide = false;

        $result = [
            'free_ship_remaining' => 0,
            'free_ship_from' => $free_ship_from_amount,
            'hide' => true,
            'txt' => Configuration::get('iqitfdc_txt', $this->context->language->id),
        ];

        $currentShipping = Context::getContext()->cart->getOrderTotal(true, Cart::ONLY_SHIPPING, $product_list);

        if(!$currentShipping){
            return $result;
        }

        $tax_excluded_display = Group::getPriceDisplayMethod(Group::getCurrent()->id);

        if ($tax_excluded_display ){
            $total = Context::getContext()->cart->getOrderTotal(false, Cart::BOTH_WITHOUT_SHIPPING, $product_list);
        } else{
            $total = Context::getContext()->cart->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING, $product_list);
        }


        if ($free_ship_from == 0) {
            return $result;
        }

        if (count(Context::getContext()->cart->getOrderedCartRulesIds(CartRule::FILTER_ACTION_SHIPPING))) {
            return;
        }

        /* $priceFormatter = new PriceFormatter(); */

        if (($free_ship_from - $total) <= 0) {
            $free_ship_remaining = 0;
            $hide = true;
        } else {
            /* $free_ship_remaining = $priceFormatter->format(); */
            $free_ship_remaining = Context::getContext()->currentLocale->formatPrice($free_ship_from - $total,$this->context->currency->iso_code);
        }

        /* $free_ship_from = $priceFormatter->format($free_ship_from); */
        return[
            'free_ship_remaining' => $free_ship_remaining,
            'free_ship_from' => $free_ship_from,
            'hide' => $hide,
            'txt' => Configuration::get('iqitfdc_txt', $this->context->language->id),
        ];
    }
}
