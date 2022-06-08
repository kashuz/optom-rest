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
    protected function performAuthenticationViaHeaders()
    {
        $phone = $_SERVER['HTTP_KASH_PHONE'] ?? null;
        $token = $_SERVER['HTTP_KASH_TOKEN'] ?? null;
        $cartId = $_SERVER['HTTP_KASH_CART_ID'] ?? null;

        if (!isset($phone, $token, $cartId)) {
            return false;
        } else {
            return $this->login(Validate::cleanKoreanPhoneNumber($phone), $token, $cartId);
        }
    }

    protected function findCustomerByCredentials($phone, $password)
    {
        return Customer::findByKashMobileToken($phone, $password);
    }

    protected function login(
        $phone,
        $password,
        $cartId,
        &$messageCode = null,
        &$psdata = null
    ) {
        // the most part of code is copy-pasted from removed login controller

        Hook::exec('actionAuthenticationBefore');

        $customer = $this->findCustomerByCredentials($phone, $password);

        if (isset($customer->active) && !$customer->active) {
            $psdata = $this->trans('Your account isn\'t available at this time.', [], 'Modules.Binshopsrest.Auth');
            $messageCode = 305;
        } elseif (!$customer || !$customer->id || $customer->is_guest) {
            $psdata = $this->trans("Authentication failed", [], 'Modules.Binshopsrest.Auth');
            $messageCode = 306;
        } else {
            if (!$this->context->cart) {
                $this->context->cart = new Cart($cartId);
            }
            $this->context->updateCustomer($customer);

            Hook::exec('actionAuthentication', ['customer' => $this->context->customer]);

            $messageCode = 200;
            $user = clone $this->context->customer;
            unset($user->secure_key);
            unset($user->passwd);
            unset($user->kash_mobile_token);
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
