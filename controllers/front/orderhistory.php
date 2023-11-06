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

require_once dirname(__FILE__) . '/../AbstractAuthRESTController.php';

use PrestaShop\PrestaShop\Adapter\Presenter\Order\OrderPresenter;
use modules\kashpaynet\Paynet;

class BinshopsrestOrderHistoryModuleFrontController extends AbstractAuthRESTController
{
    protected function processGetRequest()
    {
        //proccess single order
        if (Tools::getIsset('id_order')) {
            $id_order = Tools::getValue('id_order');
            if (Tools::isEmpty($id_order) or !Validate::isUnsignedId($id_order)) {

                $this->ajaxRender(json_encode([
                    'success' => false,
                    'code' => 404,
                    'message' => $this->trans('order not found', [], 'Modules.Binshopsrest.Order')
                ]));
                die;
            }

            if(isset($_SERVER['HTTP_KASH_LANG_ISO_CODE'])){
                $iso_lang = $_SERVER['HTTP_KASH_LANG_ISO_CODE'];
                $id_lang = Language::getIdByIso($iso_lang);
                $language = new Language($id_lang);
                $this->context->language = $language;
            }

            if(Tools::getIsset('Kash-Lang-Iso-Code')){
                $iso_lang = Tools::getValue('Kash-Lang-Iso-Code');
                $id_lang = Language::getIdByIso($iso_lang);
                $language = new Language($id_lang);
                $this->context->language = $language;
            }

            //there is a duplication of code but a prevention of new object creation too
            $order = new Order($id_order, $this->context->language->id);
            if (Validate::isLoadedObject($order) && $order->id_customer == $this->context->customer->id){
                $order_to_display = (new OrderPresenter())->present($order);

                if (Tools::isEmpty($id_order) or !Validate::isLoadedObject($order)) {

                    $this->ajaxRender(json_encode([
                        'success' => true,
                        'code' => 404,
                        'message' => $this->trans('order not found', [], 'Modules.Binshopsrest.Order')
                    ]));
                    die;
                } else {
                    /* $order_to_display->kash_paynet_receipt_array = $order->kash_paynet_receipt ? Paynet::translateReceipt(json_decode($order->kash_paynet_receipt, true)) : null; */

                    $kash_paynet_receipt_array = $order->kash_paynet_receipt ? Paynet::translateReceipt(json_decode($order->kash_paynet_receipt, true)) : null;
                    $note = $order->note;
                    
                    $note_array = explode("\n",$note);
                    $client_id = '';
                    if(!empty($note_array)){
                        foreach($note_array as $array){
                            $text = explode(':',$array);
                            if($text[0] == 'clientid'){
                                $client_id = trim($text[1]);
                            }
                        }
            
                        if($client_id != ''){
                            unset($kash_paynet_receipt_array['Phone number']);
                            unset($kash_paynet_receipt_array['Telefon raqami']);
                            unset($kash_paynet_receipt_array['Номер телефона']);
                            $lang = new Language($order->id_lang);
                            $client_id_text = $this->trans('Phone number',[],'Modules.Kashpaynet.Kashpaynet');
                            if(is_array($kash_paynet_receipt_array)){
                                $kash_paynet_receipt_array[$client_id_text] = $client_id;
                            }else{
                                $kash_paynet_receipt_array.='\n{$client_id_text}: {$client_id}';
                            }
                        }
                    }

                    if(isset($kash_paynet_receipt_array['Time'])){
                        $date = new DateTime(date('Y-m-d H:i:s',strtotime($kash_paynet_receipt_array['Time'])));
                        $date->add(new DateInterval('PT4H'));
                        $kash_paynet_receipt_array['Time'] = $date->format('Y-m-d H:i:s');
                    }
                    
                    $order_to_display->kash_paynet_receipt_array = $kash_paynet_receipt_array;
                        
                    $this->ajaxRender(json_encode([
                        'success' => true,
                        'code' => 200,
                        'psdata' => $order_to_display
                    ]));
                    die;
                }
            }else{
                $this->ajaxRender(json_encode([
                    'success' => false,
                    'code' => 404,
                    'message' => $this->trans('order not found', [], 'Modules.Binshopsrest.Order')
                ]));
                die;
            }
        }

        //process all orders
        $customer_orders = Order::getCustomerOrders($this->context->customer->id);
        foreach ($customer_orders as $index => $customer_order) {
            if (
                Tools::getIsset('is_paynet') && empty($customer_order['kash_paynet_receipt'])
                || !Tools::getIsset('is_paynet') && !empty($customer_order['kash_paynet_receipt'])
            ) {
                unset($customer_orders[$index]);
            }
        }

        $this->ajaxRender(json_encode([
            'success' => true,
            'code' => 200,
            'psdata' => array_values($customer_orders),
        ]));
        die;
    }
}
