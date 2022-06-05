<?php
/**
 * BINSHOPS
 *
 * @author BINSHOPS
 * @copyright BINSHOPS
 *
 */

require_once dirname(__FILE__) . '/../AbstractPaymentRESTController.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use modules\kashcoam\Coam;
use modules\kashcoam\CoamException;

/**
 * Based on ps_wirepayment controller source code.
 *
 * Class BinshopsrestPs_coamModuleFrontController
 */
class BinshopsrestPs_coamModuleFrontController extends AbstractPaymentRESTController
{
    protected function processRESTPayment(){
        $cart = $this->context->cart;

        // Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'kash_coam') {
                $authorized = true;
                break;
            }
        }
        if (!$authorized){
            $this->ajaxRender(json_encode([
                'success' => false,
                'code' => 303,
                'message' => $this->trans('This payment method is not available.', [], 'Modules.Binshopsrest.Payment')
            ]));
            die;
        }

        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)){
            $this->ajaxRender(json_encode([
                'success' => false,
                'code' => 301,
                'message' => $this->trans('payment processing failed', [], 'Modules.Binshopsrest.Payment')
            ]));
            die;
        }

        $module = Module::getInstanceByName('kash_coam');

        $error = null;
        $transaction = null;
        try {
            $coam = new Coam($module);
            $transaction = $coam->processPayment(
                // @see README.md in kash_coam module
                $cart->id,
                $cart->getOrderTotal(true, Cart::BOTH),
                $customer->id,
                $customer->email
            );

            $module->validateOrder(
                $cart->id,
                Configuration::get('PS_OS_PAYMENT'),
                $transaction->amount,
                $module->name,
                null,
                [
                    'transaction_id' => $transaction->id,
                    'transaction_datetime' => $transaction->dateTime
                ],
                $this->context->currency->id,
                false,
                $customer->secure_key
            );

            $this->ajaxRender(json_encode([
                'success' => true,
                'code' => 200,
                'order_id' => $module->current_order
            ]));
            die;
        } catch (CoamException $e) {
            $error = null;
            if (count($e->errors)) {
                $error = implode(PHP_EOL, $e->errors);
            } else {
                $error = $e->getMessage();
            }
            $this->ajaxRender(json_encode([
                'success' => false,
                'code' => 500,
                'message' => $error,
            ]));
            die;
        } catch (Exception $e) {
            $error = $e->getMessage();
            PrestaShopLogger::addLog(
                $error,
                3,
                null,
                $module->currentOrder ? 'Order' : 'Cart',
                $module->currentOrder ? $module->currentOrder : $cart->id,
                true
            );
            $this->ajaxRender(json_encode([
                'success' => false,
                'code' => 500,
                'message' => $error,
            ]));
            die;
        }
    }
}
