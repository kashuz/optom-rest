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

class BinshopsrestUserTokenModuleFrontController extends AbstractRESTController
{
    protected function processPostRequest()
    {
        $g_token = Tools::getValue('g_token',0);

        if(!$g_token){
            PrestaShopLogger::addLog($this->trans('Token not provided'),0,0,'customer',0,true);
            $this->ajaxRender(json_encode([
                'code' => 301,
                'success' => false,
                'message' => $this->trans('Token not provided')
            ]));
        }else{
            $id_lang = $this->context->language->id;
            $sql = 'select * from `'._DB_PREFIX_.'user_gtoken` where g_token = \''.$g_token.'\'';
            $id_customer = Tools::getValue('id_user',0);
            PrestaShopLogger::addLog(print_r(Tools::getAllValues(),true),0,0,'customer',$id_customer,true);
            PrestaShopLogger::addLog(print_r($_SERVER,true),0,0,'customer',$id_customer,true);
            if(empty(Db::getInstance()->getRow($sql))){
                if(Db::getInstance()->execute('insert into `'._DB_PREFIX_.'user_gtoken` (g_token,id_lang,id_customer,date_upd) values(\''.$g_token.'\','.$id_lang.','.$id_customer.',now()) on duplicate key update g_token = \''.$g_token.'\' and id_lang = '.$id_lang.' and id_customer = '.$id_customer.' and date_upd = now()')){
                    $this->ajaxRender(json_encode([
                        'code' => 200,
                        'success' => true,
                        'message' => $this->trans('Processed successfully')
                    ]));
                }else{
                    PrestaShopLogger::addLog($this->trans('Token could not be saved for device id %s',[$g_token]));
                }
            }else{
                $sql = 'update `'._DB_PREFIX_.'user_gtoken` set id_lang = '.$id_lang.', date_upd = now()';
                if(Tools::getIsset('id_user')){
                    $sql.=', id_customer = '.Tools::getValue('id_user');
                }
                $sql.=' where g_token = \''.$g_token.'\'';
                if(Db::getInstance()->execute($sql)){
                    $this->ajaxRender(json_encode([
                        'code' => 200,
                        'success' => true,
                        'message' => $this->trans('Processed successfully')
                    ]));
                }else{
                    PrestaShopLogger::addLog($this->trans('Language could not be updated for device id %s',[$g_token]));
                }
            }
        }
        die;
    }
}
