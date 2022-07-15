<?php

require_once dirname(__FILE__) . '/../AbstractRESTController.php';

class BinshopsrestCmsModuleFrontController extends AbstractRESTController
{
    protected function processGetRequest()
    {
        $sql = '
			SELECT *
			FROM `' . _DB_PREFIX_ . 'cms_lang` c
			WHERE c.`id_lang` = ' . (int) $this->context->language->id
        . (
            strlen(Tools::getValue('link_rewrite')) ?
                ' AND c.`link_rewrite` = "' . pSQL(Tools::getValue('link_rewrite')) . '"'
                : ''
            );

        $this->ajaxRender(json_encode([
            'code' => 200,
            'success' => true,
            'psdata' => Db::getInstance()->query($sql)->fetchAll(PDO::FETCH_ASSOC)
        ]));
        die;
    }
}
