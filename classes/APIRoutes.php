<?php

class APIRoutes
{
    public static final function getRoutes(): array
    {
        return [
            'module-binshopsrest-register' => [
                'rule' => 'rest/register',
                'keywords' => [],
                'controller' => 'register',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-logout' => [
                'rule' => 'rest/logout',
                'keywords' => [],
                'controller' => 'logout',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-accountinfo' => [
                'rule' => 'rest/accountinfo',
                'keywords' => [],
                'controller' => 'accountinfo',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-bootstrap' => [
                'rule' => 'rest/bootstrap',
                'keywords' => [],
                'controller' => 'bootstrap',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-lightbootstrap' => [
                'rule' => 'rest/lightbootstrap',
                'keywords' => [],
                'controller' => 'lightbootstrap',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-productdetail' => [
                'rule' => 'rest/productdetail',
                'keywords' => [],
                'controller' => 'productdetail',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-orderhistory' => [
                'rule' => 'rest/orderhistory',
                'keywords' => [],
                'controller' => 'orderhistory',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-cart' => [
                'rule' => 'rest/cart',
                'keywords' => [],
                'controller' => 'cart',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-categoryproducts' => [
                'rule' => 'rest/categoryProducts',
                'keywords' => [],
                'controller' => 'categoryproducts',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-productsearch' => [
                'rule' => 'rest/productSearch',
                'keywords' => [],
                'controller' => 'productsearch',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-featuredproducts' => [
                'rule' => 'rest/featuredproducts',
                'keywords' => [],
                'controller' => 'featuredproducts',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-address' => [
                'rule' => 'rest/address',
                'keywords' => [],
                'controller' => 'address',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-alladdresses' => [
                'rule' => 'rest/alladdresses',
                'keywords' => [],
                'controller' => 'alladdresses',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-addressform' => [
                'rule' => 'rest/addressform',
                'keywords' => [],
                'controller' => 'addressform',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-setaddresscheckout' => [
                'rule' => 'rest/setaddresscheckout',
                'keywords' => [],
                'controller' => 'setaddresscheckout',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-listcomments' => [
                'rule' => 'rest/listcomments',
                'keywords' => [],
                'controller' => 'listcomments',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-postcomment' => [
                'rule' => 'rest/postcomment',
                'keywords' => [],
                'controller' => 'postcomment',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-hello' => [
                'rule' => 'rest',
                'keywords' => [],
                'controller' => 'hello',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-ps_coam' => [
                'rule' => 'rest/ps_coam',
                'keywords' => [],
                'controller' => 'ps_coam',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
            'module-binshopsrest-wishlist' => [
                'rule' => 'rest/wishlist',
                'keywords' => [],
                'controller' => 'wishlist',
                'params' => [
                    'fc' => 'module',
                    'module' => 'binshopsrest'
                ]
            ],
        ];
    }
}
