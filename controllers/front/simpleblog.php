<?php

require_once dirname(__FILE__) . '/../AbstractRESTController.php';

class BinshopsrestSimpleblogModuleFrontController extends AbstractRESTController
{
    protected function processGetRequest()
    {
        $this->ajaxRender(json_encode([
            'code' => 200,
            'success' => true,
            'psdata' => SimpleBlogPost::getAllAvailablePosts($this->context->language->id)
        ]));
        die;
    }
}
