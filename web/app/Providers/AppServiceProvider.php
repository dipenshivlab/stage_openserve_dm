<?php

namespace App\Providers;

use App\Lib\BulkOperationsFinish;
use App\Lib\Handlers\CustomerCreate;
use App\Lib\Handlers\CustomerDelete;
use App\Lib\Handlers\CustomerUpdate;
use App\Lib\DbSessionStorage;
use App\Lib\Handlers\AppUninstalled;
use App\Lib\Handlers\Privacy\CustomersDataRequest;
use App\Lib\Handlers\Privacy\CustomersRedact;
use App\Lib\Handlers\Privacy\ShopRedact;
use App\Lib\Handlers\OrderCreate;
use App\Lib\Handlers\OrderDelete;
use App\Lib\Handlers\OrderUpdate;
use App\Lib\Handlers\ProductDelete;
use App\Lib\Handlers\ProductUpdate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Shopify\Context;
use Shopify\ApiVersion;
use Shopify\Webhooks\Registry;
use Shopify\Webhooks\Topics;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     * @throws \Shopify\Exception\MissingArgumentException
     */
    public function boot()
    {
        $host = str_replace('https://', '', env('HOST', 'not_defined'));

        $customDomain = env('SHOP_CUSTOM_DOMAIN', null);
        Context::initialize(
            env('SHOPIFY_API_KEY', 'not_defined'),
            env('SHOPIFY_API_SECRET', 'not_defined'),
            env('SCOPES', 'not_defined'),
            $host,
            new DbSessionStorage(),
            ApiVersion::LATEST,
            true,
            false,
            null,
            '',
            null,
            (array)$customDomain,
        );

        URL::forceRootUrl("https://$host");
        URL::forceScheme('https');

        Registry::addHandler(Topics::APP_UNINSTALLED, new AppUninstalled());

        /*
         * This sets up the mandatory privacy webhooks. You’ll need to fill in the endpoint to be used by your app in
         * the “Privacy webhooks” section in the “App setup” tab, and customize the code when you store customer data
         * in the handlers being registered below.
         *
         * More details can be found on shopify.dev:
         * https://shopify.dev/docs/apps/webhooks/configuration/mandatory-webhooks
         *
         * Note that you'll only receive these webhooks if your app has the relevant scopes as detailed in the docs.
         */
        Registry::addHandler('CUSTOMERS_DATA_REQUEST', new CustomersDataRequest());
        Registry::addHandler('CUSTOMERS_REDACT', new CustomersRedact());
        Registry::addHandler('SHOP_REDACT', new ShopRedact());
        Registry::addHandler(Topics::PRODUCTS_UPDATE, new ProductUpdate());
        Registry::addHandler(Topics::PRODUCTS_DELETE, new ProductDelete());
        Registry::addHandler(Topics::CUSTOMERS_CREATE, new CustomerCreate());
        Registry::addHandler(Topics::CUSTOMERS_UPDATE, new CustomerUpdate());
        Registry::addHandler(Topics::CUSTOMERS_DELETE, new CustomerDelete());
        Registry::addHandler(Topics::ORDERS_CREATE, new OrderCreate());
        Registry::addHandler(Topics::ORDERS_UPDATED, new OrderUpdate());
        Registry::addHandler(Topics::ORDERS_DELETE, new OrderDelete());
        // Registry::addHandler('BULK_OPERATIONS_FINISH', new BulkOperationsFinish());
    }
}
