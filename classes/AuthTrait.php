<?php
/**
 * BINSHOPS
 *
 * @author BINSHOPS
 * @copyright BINSHOPS
 *
 */

trait AuthTrait
{
    protected function login(
        &$messageCode = null,
        &$psData = null
    ) {
        // code is copy-pasted from removed login controller

        if (empty($_POST)) {
            $_POST = json_decode(Tools::file_get_contents('php://input'), true);
        }

        Hook::exec('actionAuthenticationBefore');
        $customer = new Customer();
        $customer->useRestOtpLifetime = true;
        $authentication = $customer->getByEmail(
            Validate::cleanKoreanPhoneNumber(Tools::getValue('kash_phone')),
            Tools::getValue('password')
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

        return is_array($psdata) && isset($psdata['customer_id']) && $psdata['customer_id'] && $messageCode == 200;
    }
}