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

class BinshopsrestAccounteditModuleFrontController extends AbstractAuthRESTController
{
    protected function processPostRequest()
    {
        $_POST = json_decode(Tools::file_get_contents('php://input'), true);

        $psdata = null; $message = "success";
        $messageCode = 0;
        $success = true;
        list ($firstName, $lastName) = KashUtils::parseFullName(Tools::getValue('kash_full_name'));
        $phone = Validate::cleanKoreanPhoneNumber(Tools::getValue('kash_phone'));
        $password = Tools::getValue('password');

        if (empty($phone)) {
            $message = $this->trans("Phone is required", [], 'Modules.Binshopsrest.Accountedit');
            $messageCode = 301;
            $success = false;
        } elseif (!Validate::isKoreanPhoneNumber($phone)) {
            $message = $this->trans("Invalid phone number", [], 'Modules.Binshopsrest.Accountedit');
            $messageCode = 302;
            $success = false;
        } elseif (($customerId = Customer::customerExistsByPhone($phone))
            && $customerId != $this->context->customer->id) {
            $message = $this->trans('The phone is already used, please choose another one.', [], 'Modules.Binshopsrest.Accountedit');
            $messageCode = 302;
            $success = false;
        } elseif (empty($firstName) || empty($lastName)) {
            $success = false;
            $message = $this->trans("Full name is required", [], 'Modules.Binshopsrest.Accountedit');
            $messageCode = 303;
        } elseif (!Validate::isCustomerName($firstName) || !Validate::isCustomerName($lastName)){
            $success = false;
            $message = $this->trans("Invalid name format", [], 'Modules.Binshopsrest.Accountedit');
            $messageCode = 311;
        } elseif (!strlen($password)) {
            $customer = new Customer($this->context->customer->id);
            if ($customer->kash_phone !== $phone) {
                $customer->newKashPhone = $phone;
            }
            $resultMessage = null;
            $customer->sendOtpToPhone($resultMessage);
            $messageCode = 200;
            $psdata = array(
                'is_otp_sent' => true,
                'otp_repeat_interval' => Customer::OTP_REPEAT_INTERVAL,
                'message' => $resultMessage,
            );
        } elseif (
            !($customer = (new Customer())->getByEmail($this->context->customer->kash_phone, $password))
            || $customer->id != $this->context->customer->id
        ) {
            $success = false;
            $message = $this->trans("Authentication failed", [], 'Modules.Binshopsrest.Auth');
            $messageCode = 306;
        } else {
            try {
                $cp = new CustomerPersister(
                    $this->context,
                    $this->get('hashing'),
                    $this->getTranslator(),
                    false
                );

                $customer = new Customer($this->context->customer->id);
                $customer->firstname = $firstName;
                $customer->lastname = $lastName;
                $customer->kash_full_name = Tools::getValue('kash_full_name');
                $customer->kash_phone = $phone;

                $status = $cp->save(
                    $customer,
                    null,
                    '',
                    false
                );

                if ($status) {
                    $messageCode = 200;
                    $message = 'User updated successfully';
                    $psdata = array(
                        'registered' => $status,
                        'message' => $this->trans('User updated successfully', [], 'Modules.Binshopsrest.Account'),
                        'customer_id' => $customer->id,
                        'session_data' => (int)$this->context->cart->id
                    );
                } else {
                    $success = false;
                    $messageCode = 350;
                    $message = 'could not update customer';
                    $psdata = array(
                        'registered' => $status,
                        'message' => $this->trans('Password Incorrect', [], 'Modules.Binshopsrest.Account'),
                        'customer_id' => $customer->id,
                        'session_data' => (int)$this->context->cart->id
                    );
                }
            } catch (Exception $exception) {
                $messageCode = 300;
                $message = $exception->getMessage();
                $success = false;
            }
        }

        $this->ajaxRender(json_encode([
            'success' => $success,
            'code' => $messageCode,
            'psdata' => $psdata,
            'message' => $message
        ]));
        die;
    }
}
