<?php

require_once dirname(__FILE__) . '/../AbstractProductListingRESTController.php';
require_once dirname(__FILE__) . '/../../classes/RESTProductLazyArray.php';
define('PRICE_REDUCTION_TYPE_PERCENT', 'percentage');

use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Category\CategoryProductSearchProvider;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery;
use PrestaShop\PrestaShop\Core\Product\Search\SortOrder;

class BinshopsrestCategorytreenewModuleFrontController extends AbstractProductListingRESTController
{
    protected $imageRetriever;
    protected $category;

    protected function processGetRequest()
    {
        $this->imageRetriever = new ImageRetriever(
            $this->context->link
        );

		$caching_sql = 'SELECT `response_data_json` as response_data FROM `kash_categorytree_caching` WHERE `record_id` = "1" limit 1';
        $caching_rs = Db::getInstance()->executeS($caching_sql);
		foreach ($caching_rs as $caching_row) {
			echo $caching_row['response_data'];
		}
        die;

    }

    protected function setAttributes(&$categories)
    {
        foreach ($categories as &$category) {
            $this->category = new Category($category['id_category']);

            $category['images'] = $this->imageRetriever->getImage($this->category, $this->category->id_image);
            /*$category['num_of_products'] = $this->category->getProducts(
                $this->context->language->id,
                1,
                1,
                null,
                null,
                true
            );

            $category['products'] = $this->getProducts($category['id_category']);*/

            if (!empty($category['children'])) {
                $this->setAttributes($category['children']);
            }
        }
        unset($category);
    }

    protected function getProducts($categoryId)
    {
        $variables = $this->getProductSearchVariables();
        $productList = $variables['products'];

        $settings = $this->getProductPresentationSettings();

        foreach ($productList as $key => $product) {
            $populated_product = (new ProductAssembler($this->context))
                ->assembleProduct($product);

            $lazy_product = new RESTProductLazyArray(
                $settings,
                $populated_product,
                $this->context->language,
                new \PrestaShop\PrestaShop\Adapter\Product\PriceFormatter(),
                $this->imageRetriever,
                $this->context->getTranslator()
            );

            $productList[$key] = $lazy_product->getProduct();
        }

        return $productList;
    }

    public function getListingLabel()
    {
        return '';
    }

    protected function getProductSearchQuery()
    {
        return null;
    }

    protected function getDefaultProductSearchProvider()
    {
        return new CategoryProductSearchProvider(
            $this->getTranslator(),
            $this->category
        );
    }
}
