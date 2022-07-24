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

require_once dirname(__FILE__) . '/../AbstractAuthRESTController.php';
require_once _PS_MODULE_DIR_ . 'psgdpr/controllers/front/ExportDataToCsv.php';
require_once _PS_MODULE_DIR_ . 'psgdpr/controllers/front/ExportDataToPdf.php';

class BinshopsrestGdprModuleFrontController extends AbstractAuthRESTController
{
    protected function processGetRequest()
    {
        $_GET['module'] = 'psgdpr';
        if (Tools::getValue('type') === 'csv') {
            $this->startOutput();
            GDPRLog::addLog($this->context->customer->id, 'exportCsv', 0);
            $controller = new psgdprExportDataToCsvModuleFrontController();
            $controller->exportDataToCsv($this->context->customer->id);
        } elseif (Tools::getValue('type') === 'pdf') {
            $this->startOutput();
            GDPRLog::addLog($this->context->customer->id, 'exportPdf', 0);
            $controller = new psgdprExportDataToPdfModuleFrontController();
            $controller->exportDataToPdf($this->context->customer->id);
        } else {
            $this->ajaxRender(json_encode([
                'success' => false,
                'code' => 500,
                'psdata' => [],
                'message' => 'Unknown export type has been requested.'
            ]));
            die;
        }
    }

    protected function startOutput()
    {
        register_shutdown_function(function () {
            $output = ob_get_clean();
            if (false === strpos($output, '<!DOCTYPE html>')) {
                $success = true;
                $code = 200;
                $message = 'Export has been successfully performed.';
            } else {
                $success = false;
                $code = 500;
                $message = 'Internal error in psgdpr module.';
            }

            $this->ajaxRender(json_encode([
                'success' => $success,
                'code' => $code,
                'psdata' => [
                    'output' => base64_encode($output),
                ],
                'message' => $message,
            ]));
            die;
        });
    }
}
