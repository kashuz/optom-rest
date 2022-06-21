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

        if (empty($firstName)) {
            $success = false;
            $message = "First name required";
            $messageCode = 305;
        } elseif (empty($lastName)) {
            $success = false;
            $message = "Last name required";
            $messageCode = 306;
        } elseif (!Validate::isCustomerName($firstName)){
            $success = false;
            $message = "firstname bad format";
            $messageCode = 311;
        } elseif (!Validate::isCustomerName($lastName)){
            $success = false;
            $message = "lastname bad format";
            $messageCode = 312;
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
