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
    protected function processPostRequest()
    {
        $_POST = json_decode(Tools::file_get_contents('php://input'), true);

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
            // copy-pasted from removed login controller

            Hook::exec('actionAuthenticationBefore');
            $customer = new Customer();
            $authentication = $customer->getByEmail(
                $phone,
                $password
            );

            if (isset($authentication->active) && !$authentication->active) {
                $psdata = $this->trans('Your account isn\'t available at this time.', [], 'Modules.Binshopsrest.Auth');
                $messageCode = 305;
            } elseif (!$authentication || !$customer->id || $customer->is_guest) {
                $psdata = $this->trans("Authentication failed", [], 'Modules.Binshopsrest.Auth');
                $messageCode = 306;
            } else {
                $this->context->updateCustomer($customer);

                Hook::exec('actionAuthentication', ['customer' => $this->context->customer]);

                $messageCode = 200;
                $user = $this->context->customer;
                unset($user->secure_key);
                unset($user->passwd);
                unset($user->last_passwd_gen);
                unset($user->reset_password_token);
                unset($user->reset_password_validity);

                $psdata = array(
                    'status' => 'success',
                    'message' => $this->trans('User login successfully', [], 'Modules.Binshopsrest.Auth'),
                    'customer_id' => $customer->id,
                    'session_data' => (int)$this->context->cart->id,
                    'cart_count' => Cart::getNbProducts($this->context->cookie->id_cart),
                    'user' => $user
                );

                // Login information have changed, so we check if the cart rules still apply
                CartRule::autoRemoveFromCart($this->context);
                CartRule::autoAddToCart($this->context);
            }
        } elseif ($customerId = Customer::customerExistsByPhone($phone)) {
            $customer = new Customer($customerId);
            $resultMessage = null;
            $customer->sendOtpToPhone($resultMessage);

            $messageCode = 200;
            $psdata = array(
                'is_otp_sent' => true,
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
                $customer = new Customer();
                $customer->firstname = $firstName;
                $customer->lastname = $lastName;
                $customer->kash_full_name = Tools::getValue('kash_full_name');
                $customer->kash_phone = Validate::cleanKoreanPhoneNumber($phone);
                $customer->id_shop = (int)$this->context->shop->id;

                $status = $cp->save($customer, KashUtils::generateRandomString());

                $resultMessage = null;
                $customer->sendOtpToPhone($resultMessage);

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

        $this->ajaxRender(json_encode([
            'success' => $success,
            'code' => $messageCode,
            'psdata' => $psdata
        ]));
        die;
    }
}
