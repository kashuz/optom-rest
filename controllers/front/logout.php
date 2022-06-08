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

class BinshopsrestLogoutModuleFrontController extends AbstractAuthRESTController
{
    protected function processGetRequest()
    {
        $this->context->customer->myMobileLogout();

        $this->ajaxRender(json_encode([
            'code' => 200,
            'success' => true,
            'message' => $this->trans('Customer logged out successfully', [], 'Modules.Binshopsrest.Auth')
        ]));
        die;
    }
}
