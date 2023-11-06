<?php

require_once dirname(__FILE__) . '/../AbstractProductListingRESTController.php';
require_once dirname(__FILE__) . '/../../classes/RESTProductLazyArray.php';
define('PRICE_REDUCTION_TYPE_PERCENT', 'percentage');

class BinshopsrestSellerdetailModuleFrontController extends AbstractProductListingRESTController
{


    protected function processGetRequest()
    {
        if (isset($this->context->cart)) {
            $idCustomer = $this->context->cart->id_customer;
        }
        if (!(int)Tools::getValue('id_seller', 0)) {
            $this->ajaxRender(json_encode([
                'code' => 301,
                'message' => $this->trans('Seller id not specified', [], 'Modules.Binshopsrest.Product')
            ]));
            die;
        }
        $id_seller = Tools::getValue('id_seller', 0);
        $id_category = Tools::getValue('id_category');

        if (Module::isEnabled('marketplace')) {
            $seller_details = $this->getSellerDetails($id_seller,$id_category);
            $this->ajaxRender(json_encode([
                'success' => true,
                'code' => 200,
                'psdata' => $seller_details
            ]));
            die;
        } else {
            $this->ajaxRender(json_encode([
                'code' => 301,
                'message' => $this->trans('This page has been moved. Please contact administrator', [], 'Modules.Binshopsrest.Product')
            ]));
            die;
        }
    }

    protected function getSellerDetails($id_seller,$id_category=false)
    {
        $sellerObj = new WkMpSeller($id_seller);
        $mpSeller = WkMpSeller::getSellerByLinkRewrite($sellerObj->link_rewrite, $this->context->language->id);
        if(isset($this->context->cart)){
            $idCustomer = $this->context->cart->id_customer;
        }
        if ($mpSeller) {
            $idSeller = $mpSeller['id_seller'];
            if ($mpSeller['active']) {
                //Display price tax Incl or excl and price hide/show according to customer group settings
                $displayPriceTaxIncl = 1;
                $showPriceByCustomerGroup = 1;
                if ($groupAccess = Group::getCurrent()) {
                    if (isset($groupAccess->price_display_method) && $groupAccess->price_display_method) {
                        $displayPriceTaxIncl = 0; //Display tax incl price
                    }
                    if (empty($groupAccess->show_prices)) {
                        $showPriceByCustomerGroup = 0; //Don't display product price
                    }
                }

                $products = $this->getSellerProducts($id_seller);
                if($id_category){
                    $mp_product = $this->getMpProductByCategory($id_category, $products);
                }else{
                    $mp_product = $products;
                }



                $objReview = new WkMpSellerReview();
                if ($reviews = $objReview->getReviewsByConfiguration($idSeller)) {

                    $mpSeller['reviews'] = array(
                        'avg_rating' => $reviews['avg_rating'],
                        'reviews' => $reviews['reviews'],
                    );
                }

                if ($mpSeller['profile_image'] && file_exists(_PS_MODULE_DIR_ . 'marketplace/views/img/seller_img/' . $mpSeller['profile_image'])) {
                    $mpSeller['seller_img_path'] = $this->context->shop->getBaseURL(true, true)._MODULE_DIR_ . 'marketplace/views/img/seller_img/' . $mpSeller['profile_image'];
                    $mpSeller['seller_img_exist'] =  1;
                } else {
                    $mpSeller['seller_img_path'] = $this->context->shop->getBaseURL(true, true)._MODULE_DIR_ . 'marketplace/views/img/seller_img/defaultimage.jpg';
                }

                //Check if seller banner exist
                
                $sellerBannerPath = WkMpSeller::getSellerBannerLink($mpSeller);
                if(!$sellerBannerPath){
                    $sellerBannerPath = $this->context->shop->getBaseURL(true, true).'modules/marketplace/views/img/seller_img/defaultsellerbanner.png';
                }else{
                    $sellerBannerPath= $this->context->shop->getBaseURL(true, true).$sellerBannerPath;
                }
                $mpSeller['seller_banner_path'] = $sellerBannerPath;

                $sellerBannerPath = WkMpSeller::getShopBannerLink($mpSeller);
                if(!$sellerBannerPath){
                    $sellerBannerPath = $this->context->shop->getBaseURL(true, true).'modules/marketplace/views/img/seller_img/defaultshopbanner.png';
                }else{
                    $sellerBannerPath= $this->context->shop->getBaseURL(true, true).$sellerBannerPath;
                }
                $mpSeller['seller_shop_banner_path'] = $sellerBannerPath;
                
                $shopImagePath = WkMpSeller::getShopImageLink($mpSeller);
                if(!$shopImagePath){
                    $shopImagePath = $this->context->shop->getBaseURL(true, true).'modules/marketplace/views/img/seller_img/defaultimage.jpg';
                }else{
                    $shopImagePath=$this->context->shop->getBaseURL(true, true).$shopImagePath;
                }
                $mpSeller['seller_shop_logo_path'] = $shopImagePath;

                $loginShop = '';
                // Get login user marketplace shop details if exist for seller can't review yourself
                if ($idCustomer) {
                    $loginCustomer = WkMpSeller::getSellerDetailByCustomerId($idCustomer);
                    if ($currenctCustomerReview = WkMpSellerReview::getReviewByCustomerIdAndSellerId($idCustomer, $idSeller)) {
                        $mpSeller['currenct_cust_review'] = $currenctCustomerReview;
                    } elseif ($loginCustomer) {
                        $loginShop = $loginCustomer['link_rewrite'];
                    }
                }

                $mpSeller['login_mp_shop_name'] = $loginShop;

                if ($mpSeller['id_country']) {
                    $mpSeller['country'] = Country::getNameById($this->context->language->id, $mpSeller['id_country']);
                }
                if ($mpSeller['id_state']) {
                    $mpSeller['state'] = State::getNameById($mpSeller['id_state']);
                }

                if (Configuration::get('WK_MP_CONTACT_SELLER_SETTINGS')) {
                    //If admin allowed only registered customers to contact with seller in configuration
                    if ($this->context->customer->id) {
                        $mpSeller['contactSellerAllowed'] = 1;
                    }
                } else {
                    //Anyone can contact to seller
                    $mpSeller['contactSellerAllowed'] = 1;
                }

                
                $catg_details = $this->getMpProductCategoryCount($products);
                if(!empty($catg_details)){
                    foreach($catg_details as $key=>$category){
                        if($category['id_category'] == Configuration::get('PS_ROOT_CATEGORY')){
                            unset($catg_details[$key]);
                        }
                    }
                }
                $mpSeller['seller_categories'] = array_values($catg_details);

                $mpSeller['link_rewrite'] = Context::getContext()->link->getModuleLink('marketplace', 'shopstore', ['mp_shop_name' => $mpSeller['link_rewrite'], 'id_seller' => $mpSeller['id_seller']]);
                
                // Assign the seller details view vars
                WkMpSeller::checkSellerAccessPermission($mpSeller['seller_details_access']);
                
                $mpSeller['nb_products'] = count($mp_product);

                foreach($mp_product as &$product){
                    $product['seller_info'] = $mpSeller;
                    $product['is_seller_product'] = true;
                }

                return [
                    'seller' => $mpSeller,
                    'products' => $mp_product
                ];
            } else {
                $this->ajaxRender(json_encode([
                    'code' => 301,
                    'message' => $this->trans('Seller not available', [], 'Modules.Binshopsrest.Product')
                ]));
                die; // seller is deactivated by admin
            }
        } else {
            $this->ajaxRender(json_encode([
                'code' => 301,
                'message' => $this->trans('Seller does not exists', [], 'Modules.Binshopsrest.Product')
            ]));
            die;
        }
    }

    protected function getSellerProducts($id_seller)
    {
        $mpProduct = WkMpSellerProduct::getSellerProductWithPs($id_seller, true,1,false,false,'mpsp.position','ASC',100000);
        $productList = array();
        if ($mpProduct) {
            $activeProduct = array();
            $retriever = new \PrestaShop\PrestaShop\Adapter\Image\ImageRetriever(
                $this->context->link
            );
    
            
            $settings = $this->getProductPresentationSettings();

            if (Module::isEnabled('mpsellerpricecomparision')) {
                foreach ($mpProduct as $key => $productDetails) {
                    $product = new Product(
                        $productDetails['id_ps_product'],
                        true,
                        $this->context->language->id
                    );

                    if ($displayPriceTaxIncl) {
                        $productDetails['retail_price'] = Tools::displayPrice(
                            $product->getPriceWithoutReduct(false, $product->getWsDefaultCombination())
                        );
                        $productDetails['price'] = Tools::displayPrice($product->getPrice(true));
                    } else {
                        $productDetails['retail_price'] = Tools::displayPrice(
                            $product->getPriceWithoutReduct(true, $product->getWsDefaultCombination())
                        );
                        $productDetails['price'] = Tools::displayPrice($product->getPrice(false));
                    }

                    $wkNormalProduct = true;
                    if (isset($productDetails['is_global']) && isset($productDetails['id_product_global'])) {
                        if (!$productDetails['is_global'] && !$productDetails['id_product_global']) {
                            $wkNormalProduct = true;
                        } else {
                            $wkNormalProduct = false;
                        }
                    }

                    if ($wkNormalProduct) {
                        if (($productDetails['visibility'] == 'both')
                            || ($productDetails['visibility'] == 'catalog')
                        ) {
                            $activeProduct[] = $productDetails;
                        }
                    } else {
                        $activeProduct[] = $productDetails;
                    }

                    $populated_product = (new ProductAssembler($this->context))
                    ->assembleProduct($productDetails);

                    $lazy_product = new RESTProductLazyArray(
                        $settings,
                        $populated_product,
                        $this->context->language,
                        new \PrestaShop\PrestaShop\Adapter\Product\PriceFormatter(),
                        $retriever,
                        $this->context->getTranslator()
                    );

                    $productList[$key] = $lazy_product->getProduct();
                }
            } else {
                foreach ($mpProduct as $key => $product) {
                    $populated_product = (new ProductAssembler($this->context))
                    ->assembleProduct($product);

                    $lazy_product = new RESTProductLazyArray(
                        $settings,
                        $populated_product,
                        $this->context->language,
                        new \PrestaShop\PrestaShop\Adapter\Product\PriceFormatter(),
                        $retriever,
                        $this->context->getTranslator()
                    );

                    $productList[$key] = $lazy_product->getProduct();
                }
            }
            return $productList;
        }
        return [];
    }

    public function getMpProductCategoryCount($mpProduct)
    {
        $mpCategory = array();
        if ($mpProduct) {
            $idSeller = $mpProduct[0]['id_seller'];
            if ($idSeller && Configuration::get('WK_MP_PRODUCT_CATEGORY_RESTRICTION')) {
                $objMpSeller = new WkMpSeller($idSeller);
                if ($objMpSeller->category_permission) {
                    $sellerAllowedCatIds = json_decode(($objMpSeller->category_permission));
                }
            }
            if (!isset($sellerAllowedCatIds) || empty($sellerAllowedCatIds)) {
                $idCategories = array();
                $rootIdCategory = Category::getRootCategory()->id;
                $categories = Category::getAllCategoriesName();
                foreach ($categories as $category) {
                    if ($rootIdCategory != $category) {
                        $idCategories[] = $category['id_category'];
                    }
                }
                $sellerAllowedCatIds = $idCategories;
            }

            foreach ($mpProduct as $p) {
                if ($p['active']) {
                    $product = new Product($p['id_ps_product'], false, $this->context->language->id);
                    $categories = $product->getCategories();
                    foreach ($categories as $catg) {
                        $category = new Category($catg, $this->context->language->id);
                        if (isset($category->id) && $category->active
                        && in_array($category->id, $sellerAllowedCatIds)) {
                            if (!array_key_exists($catg, $mpCategory)) {
                                if ($catg != Category::getRootCategory()->id) {
                                    $mpCategory[$catg] = array(
                                        'id_category' => $catg,
                                        'Name' => $category->name,
                                        'NoOfProduct' => 1,
                                    );
                                }
                            } else {
                                $mpCategory[$catg]['NoOfProduct'] += 1;
                            }
                        }
                    }
                }
            }
        }

        if ($mpCategory) {
            return $mpCategory;
        }

        return [];
    }
    public function getMpProductByCategory($idCategory, $activeProduct)
    {
        if ($activeProduct) {
            foreach ($activeProduct as $key => $mpProduct) {
                $product = new Product($mpProduct['id_ps_product'], false, $this->context->language->id);
                $catgs = $product->getCategories();
                if (!in_array($idCategory, $catgs)) {
                    unset($activeProduct[$key]);
                }
            }
        }

        return array_values($activeProduct);
    }

    public function getListingLabel(){}

    public function getProductSearchQuery(){}

    public function getDefaultProductSearchProvider(){}
}
