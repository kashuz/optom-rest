<?php
/**
 * BINSHOPS
 *
 * @author BINSHOPS
 * @copyright BINSHOPS
 *
 */

require_once dirname(__FILE__) . '/../classes/AuthTrait.php';
require_once dirname(__FILE__) . '/../classes/KashHeadersTrait.php';
require_once dirname(__FILE__) . '/../classes/KashLoggerTrait.php';

use PrestaShop\PrestaShop\Adapter\Presenter\Order\OrderPresenter;

/**
 * REST Payment Controllers should extend this class for payment processing
 */
abstract class AbstractPaymentRESTController extends ModuleFrontController
{
    use AuthTrait;
    use KashHeadersTrait;
    use KashLoggerTrait;

    public $auth = true;
    public $ssl = true;

    protected function isPostLogged()
    {
        return false;
    }

    public function init()
    {
        $this->startProfiling();

        header('Content-Type: ' . "application/json");
        $this->processKashHeaders();
        $this->performAuthenticationViaHeaders();
        if (!$this->context->customer->isLogged()) {
            $this->ajaxRender(json_encode([
                'code' => 410,
                'success' => false,
                'message' => $this->trans('User Not Authenticated', [], 'Modules.Binshopsrest.Admin')
            ]));
            die;
        }

        $this->processKashHeaders();

        parent::init();
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                $this->processGetRequest();
                break;
            case 'POST':
                $this->processPostRequest();
                break;
            case 'PATCH':
            case 'PUT':
                $this->processPutRequest();
                break;
            case 'DELETE':
                $this->processDeleteRequest();
                break;
            default:
                // throw some error or whatever
        }
    }

    protected final function processPostRequest(){
        $_POST = json_decode(Tools::file_get_contents('php://input'), true);

        $cart = $this->context->cart;

        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            $this->ajaxRender(json_encode([
                'success' => false,
                'code' => 301,
                'message' => $this->trans('Payment processing failed', [], 'Modules.Binshopsrest.Payment')
            ]));
            die;
        }

        if (Validate::isLoadedObject($this->context->cart) && $this->context->cart->OrderExists() == false){
            $this->processRESTPayment();
        } else {
            $this->ajaxRender(json_encode([
                'success' => false,
                'code' => 302,
                'message' => $this->trans('Cart cannot be loaded or an order has already been placed using this cart', [], 'Admin.Payment.Notification')
            ]));
            die;
        }

        $order_presenter = new OrderPresenter();

        $order = new Order(Order::getIdByCartId((int) ($cart->id)));
        $presentedOrder = $order_presenter->present($order);

        $this->ajaxRender(json_encode([
            'success' => true,
            'code' => 200,
            'message' => 'successful payment',
            'psdata' => [
                'order' => $presentedOrder,
                'cart_id' => $this->context->cart->id,
            ]
        ]));
        die;
    }

    protected final function processGetRequest()
    {
        $this->ajaxRender(json_encode([
            'success' => true,
            'message' => 'GET not supported on this path'
        ]));
        die;
    }

    protected final function processPutRequest()
    {
        $this->ajaxRender(json_encode([
            'success' => true,
            'message' => 'PUT not supported on this path'
        ]));
        die;
    }

    protected final function processDeleteRequest()
    {
        $this->ajaxRender(json_encode([
            'success' => true,
            'message' => 'DELETE not supported on this path'
        ]));
        die;
    }

    abstract protected function processRESTPayment();
}
