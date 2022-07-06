<?php

require_once dirname(__FILE__) . '/../AbstractRESTController.php';

use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;

class BinshopsrestCategorytreeModuleFrontController extends AbstractRESTController
{
    protected $imageRetriever;

    protected function processGetRequest()
    {
        $this->imageRetriever = new ImageRetriever(
            $this->context->link
        );

        $categories = Category::getNestedCategories();
        $this->setAttributes($categories);

        $this->ajaxRender(json_encode([
            'code' => 200,
            'success' => true,
            'psdata' => $categories
        ]));
        die;
    }

    protected function setAttributes(&$categories)
    {
        foreach ($categories as &$category) {
            $categoryObject = new Category($category['id_category']);

            $category['images'] = $this->imageRetriever->getImage($categoryObject, $categoryObject->id_image);
            $category['num_of_products'] = $categoryObject->getProducts(
                $this->context->language->id,
                1,
                1,
                null,
                null,
                true
            );

            if (!empty($category['children'])) {
                $this->setAttributes($category['children']);
            }
        }
        unset($category);
    }
}
