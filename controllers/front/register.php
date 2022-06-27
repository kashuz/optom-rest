<?php
/**
 * BINSHOPS
 *
 * @author BINSHOPS
 * @copyright BINSHOPS
 *
 */

require_once dirname(__FILE__) . '/../AbstractRESTController.php';
require_once _PS_MODULE_DIR_ . 'kash_checkout/classes/KashUtils.php';

class BinshopsrestRegisterModuleFrontController extends AbstractRESTController
{
    protected function findCustomerByCredentials($phone, $password)
    {
        $customer = new Customer();
        return $customer->getByEmail($phone, $password);
    }

    protected function processPostRequest()
    {
        $_POST = json_decode(Tools::file_get_contents('php://input'), true);

        try {
            $psdata = "";
            $messageCode = 0;
            $success = true;
            list ($firstName, $lastName) = KashUtils::parseFullName(Tools::getValue('kash_full_name'));
            $phone = Tools::getValue('kash_phone');
            $password = Tools::getValue('password');

            if (empty($phone)) {
                $psdata = $this->trans("Phone is required", [], 'Modules.Binshopsrest.Auth');
                $messageCode = 301;
            } elseif (!Validate::isKoreanPhoneNumber($phone)) {
                $psdata = $this->trans("Invalid phone number", [], 'Modules.Binshopsrest.Auth');
                $messageCode = 302;
            } elseif (!empty($password)) {
                if ($this->login($phone, $password, null, $messageCode, $psdata)) {
                    $psdata['kash_mobile_token'] = $psdata['user']->setKashMobileToken();
                }
            } elseif ($customerId = Customer::customerExistsByPhone($phone)) {
                $customer = new Customer($customerId);
                $resultMessage = null;
                $customer->sendOtpToPhone($resultMessage);

                $messageCode = 200;
                $psdata = array(
                    'is_otp_sent' => true,
                    'otp_repeat_interval' => Customer::OTP_REPEAT_INTERVAL,
                    'message' => $resultMessage,
                    'session_data' => (int)$this->context->cart->id
                );
            } elseif (empty($firstName) || empty($lastName)) {
                $psdata = $this->trans("Full name is required", [], 'Modules.Binshopsrest.Auth');
                $messageCode = 305;
            } else {
                $cp = new CustomerPersister(
                    $this->context,
                    $this->get('hashing'),
                    $this->getTranslator(),
                    false
                );
                try {
                    $phone = Validate::cleanKoreanPhoneNumber($phone);

                    $customer = new Customer();
                    $customer->firstname = $firstName;
                    $customer->lastname = $lastName;
                    $customer->kash_full_name = Tools::getValue('kash_full_name');
                    $customer->kash_phone = $phone;
                    $customer->email = $phone . Customer::DUMMY_EMAIL_DOMAIN;
                    $customer->id_shop = (int)$this->context->shop->id;

                    $status = $cp->save($customer, KashUtils::generateRandomString());

                    $resultMessage = null;
                    if ($status) {
                        $customer->sendOtpToPhone($resultMessage);
                    } else {
                        $resultMessage = implode(' ', call_user_func_array('array_merge', $cp->getErrors()));
                    }

                    $messageCode = 200;
                    $psdata = array(
                        'registered' => $status,
                        'message' => $resultMessage,
                        'session_data' => (int)$this->context->cart->id
                    );
                } catch (Exception $exception) {
                    $messageCode = 300;
                    $psdata = $exception->getMessage();
                    $success = false;
                }
            }
        } catch (Exception $e) {
            $messageCode = 500;
            $psdata = $e->getMessage();
        }

        $this->ajaxRender(json_encode([
            'success' => $success,
            'code' => $messageCode,
            'psdata' => $psdata
        ]));
        die;
    }
}
