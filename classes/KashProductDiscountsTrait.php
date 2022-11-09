<?php

use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;

trait KashProductDiscountsTrait
{
    protected function formatQuantityDiscounts($specific_prices, $price, $tax_rate, $ecotax_amount)
    {
        $priceFormatter = new PriceFormatter();

        foreach ($specific_prices as $key => &$row) {
            $row['quantity'] = &$row['from_quantity'];
            if ($row['price'] >= 0) {
                // The price may be directly set

                /** @var float $currentPriceDefaultCurrency current price with taxes in default currency */
                $currentPriceDefaultCurrency = (!$row['reduction_tax'] ? $row['price'] : $row['price'] * (1 + $tax_rate / 100)) + (float)$ecotax_amount;
                // Since this price is set in default currency,
                // we need to convert it into current currency
                $row['id_currency'];
                $currentPriceCurrentCurrency = Tools::convertPrice($currentPriceDefaultCurrency, $this->context->currency, true, $this->context);

                if ($row['reduction_type'] == 'amount') {
                    $currentPriceCurrentCurrency -= ($row['reduction_tax'] ? $row['reduction'] : $row['reduction'] / (1 + $tax_rate / 100));
                    $row['reduction_with_tax'] = $row['reduction_tax'] ? $row['reduction'] : $row['reduction'] / (1 + $tax_rate / 100);
                } else {
                    $currentPriceCurrentCurrency *= 1 - $row['reduction'];
                }
                $row['real_value'] = $price > 0 ? $price - $currentPriceCurrentCurrency : $currentPriceCurrentCurrency;
                $discountPrice = $price - $row['real_value'];

                if (Configuration::get('PS_DISPLAY_DISCOUNT_PRICE')) {
                    if ($row['reduction_tax'] == 0 && !$row['price']) {
                        $row['discount'] = $priceFormatter->format($price - ($price * $row['reduction_with_tax']));
                    } else {
                        $row['discount'] = $priceFormatter->format($price - $row['real_value']);
                    }
                } else {
                    $row['discount'] = $priceFormatter->format($row['real_value']);
                }
            } else {
                if ($row['reduction_type'] == 'amount') {
                    if (Product::$_taxCalculationMethod == PS_TAX_INC) {
                        $row['real_value'] = $row['reduction_tax'] == 1 ? $row['reduction'] : $row['reduction'] * (1 + $tax_rate / 100);
                    } else {
                        $row['real_value'] = $row['reduction_tax'] == 0 ? $row['reduction'] : $row['reduction'] / (1 + $tax_rate / 100);
                    }
                    $row['reduction_with_tax'] = $row['reduction_tax'] ? $row['reduction'] : $row['reduction'] + ($row['reduction'] * $tax_rate) / 100;
                    $discountPrice = $price - $row['real_value'];
                    if (Configuration::get('PS_DISPLAY_DISCOUNT_PRICE')) {
                        if ($row['reduction_tax'] == 0 && !$row['price']) {
                            $row['discount'] = $priceFormatter->format($price - ($price * $row['reduction_with_tax']));
                        } else {
                            $row['discount'] = $priceFormatter->format($price - $row['real_value']);
                        }
                    } else {
                        $row['discount'] = $priceFormatter->format($row['real_value']);
                    }
                } else {
                    $row['real_value'] = $row['reduction'] * 100;
                    $discountPrice = $price - $price * $row['reduction'];
                    if (Configuration::get('PS_DISPLAY_DISCOUNT_PRICE')) {
                        if ($row['reduction_tax'] == 0) {
                            $row['discount'] = $priceFormatter->format($price - ($price * $row['reduction_with_tax']));
                        } else {
                            $row['discount'] = $priceFormatter->format($price - ($price * $row['reduction']));
                        }
                    } else {
                        $row['discount'] = $row['real_value'] . '%';
                    }
                }
            }

            $row['save'] = $priceFormatter->format((($price * $row['quantity']) - ($discountPrice * $row['quantity'])));
            $row['nextQuantity'] = (isset($specific_prices[$key + 1]) ? (int)$specific_prices[$key + 1]['from_quantity'] : -1);
        }

        return $specific_prices;
    }

    protected function getQuantityDiscounts($id_product, $id_product_attribute)
    {
        $product = new Product(
            Tools::getValue('product_id', 0),
            true,
            $this->context->language->id,
            $this->context->shop->id,
            $this->context
        );

        $id_customer = (isset($this->context->customer) ? (int)$this->context->customer->id : 0);
        $id_group = (int)Group::getCurrent()->id;
        $id_country = $id_customer ? (int)Customer::getCurrentCountry($id_customer) : (int)Tools::getCountry();
        $id_currency = (int)$this->context->cookie->id_currency;
        $id_shop = $this->context->shop->id;

        $quantity_discounts = SpecificPrice::getQuantityDiscounts($id_product, $id_shop, $id_currency, $id_country, $id_group, $id_product_attribute, false, (int)$this->context->customer->id);

        $tax = (float)$product->getTaxesRate(new Address((int)$this->context->cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')}));

        $product_price = $product->getPrice(Product::$_taxCalculationMethod == PS_TAX_INC, $id_product_attribute);

        return $this->formatQuantityDiscounts($quantity_discounts, $product_price, (float)$tax, $product->ecotax);
    }
}
