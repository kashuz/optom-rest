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

class BinshopsrestSellerListModuleFrontController extends AbstractRESTController
{
    protected function processGetRequest()
    {
        require_once(_PS_MODULE_DIR_.'marketplace/marketplace.php');
        $sellers = WkMpSeller::getAllSeller(0,999,$this->context->language->id,false,true);
        if(!empty($sellers)){
            foreach($sellers as $key=>&$seller){
                if($seller['active']){
                    if ($seller['profile_image'] && file_exists(_PS_MODULE_DIR_ . 'marketplace/views/img/seller_img/' . $seller['profile_image'])) {
                        $seller['seller_img_path'] = $this->context->shop->getBaseURL(true, true).'modules/marketplace/views/img/seller_img/' . $seller['profile_image'];
                        $seller['seller_img_exist'] =  1;
                    } else {
                        $seller['seller_img_path'] = $this->context->shop->getBaseURL(true, true).'modules/marketplace/views/img/seller_img/defaultimage.jpg';
                    }

                    $sellerBannerPath = WkMpSeller::getSellerBannerLink($seller);
                    if(!$sellerBannerPath){
                        $sellerBannerPath = $this->context->shop->getBaseURL(true, true).'modules/marketplace/views/img/seller_img/defaultsellerbanner.png';
                    }else{
                        $sellerBannerPath= $this->context->shop->getBaseURL(true, true).$sellerBannerPath;
                    }
                    $seller['seller_banner_path'] = $sellerBannerPath;

                    $sellerBannerPath = WkMpSeller::getShopBannerLink($seller);
                    if(!$sellerBannerPath){
                        $sellerBannerPath = $this->context->shop->getBaseURL(true, true).'modules/marketplace/views/img/seller_img/defaultshopbanner.png';
                    }else{
                        $sellerBannerPath= $this->context->shop->getBaseURL(true, true).$sellerBannerPath;
                    }
                    $seller['seller_shop_banner_path'] = $sellerBannerPath;
                    
                    $shopImagePath = WkMpSeller::getShopImageLink($seller);
                    if(!$shopImagePath){
                        $shopImagePath = $this->context->shop->getBaseURL(true, true).'modules/marketplace/views/img/seller_img/defaultimage.jpg';
                    }else{
                        $shopImagePath=$this->context->shop->getBaseURL(true, true).$shopImagePath;
                    }
                    $seller['shop_logo'] = $shopImagePath;
                    
                    $seller['shop_url'] = Context::getContext()->link->getModuleLink('marketplace','shopstore',['mp_shop_name' => $seller['link_rewrite'],'id_seller'=>$seller['id_seller']]);
                    $seller_products = WkMpSellerProduct::getSellerProduct($seller['id_seller'],true);
                    if(!$seller_products){
                        $nb_products = 0;
                    }else{
                        $nb_products = count($seller_products);
                    }
                    $seller['nb_products'] = $nb_products;
                }else{
                    unset($sellers[$key]);
                }
            }
            usort($sellers, function ($a, $b) {
                return $a['id_seller'] <=> $b['id_seller'];
            });
        }
        $messageCode = 200;
        $success = true;

        $this->ajaxRender(json_encode([
            'success' => $success,
            'code' => $messageCode,
            'psdata' => $sellers
        ]));
        die;
    }
}
