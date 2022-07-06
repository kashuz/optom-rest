<?php

require_once dirname(__FILE__) . '/../AbstractRESTController.php';

class BinshopsrestContactformModuleFrontController extends AbstractRESTController
{
    protected function processPostRequest()
    {
        $_POST = json_decode(Tools::file_get_contents('php://input'), true);

        $psdata = null;
        $message = null;
        $messageCode = 200;
        $success = true;

        $module = Module::getInstanceByName('kash_contactform');

        try {
            $contacts = Contact::getContacts($this->context->language->id);
            $_POST['id_contact'] = $contacts[0]['id_contact'] ?? null;

            $module->sendMessage(false);
            if (!empty($this->context->controller->errors)) {
                $psdata = $this->context->controller->errors;
                $messageCode = 500;
                $success = false;
            } elseif (!empty($this->context->controller->success)) {
                $psdata = $this->context->controller->success;
            }

            $this->ajaxRender(json_encode([
                'success' => $success,
                'code' => $messageCode,
                'psdata' => $psdata,
                'message' => $message
            ]));
            die;

        } catch (Exception $e) {
            $error = $e->getMessage();
            PrestaShopLogger::addLog(
                $error,
                3,
                null,
                null,
                null,
                true
            );
            $this->ajaxRender(json_encode([
                'success' => false,
                'code' => 500,
                'message' => $error,
            ]));
            die;
        }
    }
}
