<?php

use modules\kashcoam\CoamException;
use modules\kashpaynet\Paynet;

require_once dirname(__FILE__) . '/../AbstractAuthRESTController.php';

class BinshopsrestPaynetModuleFrontController extends AbstractAuthRESTController
{
    protected function processPostRequest()
    {
        $_POST = json_decode(Tools::file_get_contents('php://input'), true);

        $exchangeRate = 0;
        $divider = 1;

        try {
            $module = Module::getInstanceByName('kash_paynet');
            $paynet = new Paynet($this->module);

            $exchangeRate = $paynet->getExchangeRate($_POST['countryCode'] ?? null);
            $divider = Paynet::EXCHANGE_RATE_DIVIDER;

            $psdata = [];

            if (!isset($_POST['action'])) {
                throw new Exception('Action is not set.');
            } elseif ($_POST['action'] === 'services') {
                $psdata['services'] = json_decode(\Configuration::get('KASH_PAYNET_SERVICES'), true);
                if (!is_array($psdata['services'])) {
                    $psdata['services'] = [];
                } else {
                    foreach ($psdata['services'] as &$service) {
                        $paynet->addCustomProperties($service);
                    }
                    unset($service);
                    $psdata['services'] = $paynet->groupAndSortServices($psdata['services']);
                }
            } elseif ($_POST['action'] === 'phoneValidation') {
                $paynet->validatePhoneNumber(
                    $_POST['countryCode'] ?? null,
                    $_POST['serviceId'] ?? null,
                    $_POST['phone'] ?? null
                );
            } elseif ($_POST['action'] === 'amountValidation') {
                $amount = $_POST['amount'] ?? null;
                $amountInLocalCurrency = $_POST['amountInLocalCurrency'] ?? null;
                $paynet->validatePaymentData(
                    $_POST['countryCode'] ?? null,
                    $_POST['serviceId'] ?? null,
                    $_POST['phone'] ?? null,
                    $amount,
                    $amountInLocalCurrency
                );
            } elseif ($_POST['action'] === 'payment') {
                $psdata['receipt'] = $paynet->pay(
                    $_POST['countryCode'] ?? null,
                    $_POST['serviceId'] ?? null,
                    $_POST['phone'] ?? ($_POST['dynamicFields'] ?? null),
                    $_POST['amount'] ?? null,
                    $_POST['amountInLocalCurrency'] ?? null
                );
            } else {
                throw new Exception('Unknown action.');
            }

            $this->ajaxRender(json_encode([
                'success' => true,
                'code' => 200,
                'psdata' => array_merge($psdata, [
                    'countries' => $module->getCountries(),
                    'inactiveCountries' => $module->getInactiveCountries(),
                    'countryRules' => Paynet::COUNTRY_RULES,
                    'exchangeRate' => $exchangeRate,
                    'divider' => $divider,
                ])
            ], JSON_INVALID_UTF8_SUBSTITUTE));
            die;
        } catch (CoamException $e) {
            $this->ajaxRender(json_encode([
                'success' => false,
                'code' => 500,
                'message' => count($e->errors) ? implode(PHP_EOL, $e->errors) : $e->getMessage(),
                'psdata' => [
                    'exchangeRate' => $exchangeRate,
                    'divider' => $divider,
                ],
            ]));
            die;
        } catch (Exception $e) {
            $this->ajaxRender(json_encode([
                'success' => false,
                'code' => 500,
                'message' => $e->getMessage(),
                'psdata' => [
                    'exchangeRate' => $exchangeRate,
                    'divider' => $divider,
                ],
            ]));
            die;
        }
    }
}