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

require_once dirname(__FILE__) . '/AbstractRESTController.php';
require_once dirname(__FILE__) . '/../classes/KashLoggerTrait.php';

/**
 * Protected REST endpoints should extend this class
 */
abstract class AbstractAuthRESTController extends AbstractRESTController
{
    use KashLoggerTrait;

    public $auth = true;
    public $ssl = true;

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

        parent::init();
    }
}
