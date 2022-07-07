<?php

require_once dirname(__FILE__) . '/../AbstractRESTController.php';

class BinshopsrestSimpleblogModuleFrontController extends AbstractRESTController
{
    protected function processGetRequest()
    {
        $this->ajaxRender(json_encode([
            'code' => 200,
            'success' => true,
            'psdata' => SimpleBlogPost::getPosts(
                $this->context->language->id,
                Tools::getValue('pageSize', 10),
                null,
                Tools::getValue('page', 0)
            )
        ]));
        die;
    }
}
