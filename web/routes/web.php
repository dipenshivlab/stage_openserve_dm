<?php

use App\Exceptions\ShopifyProductCreatorException;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerDocumentController;
use App\Http\Controllers\IspSellerContoller;
use App\Http\Controllers\OpenServeApiController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PrepaidController;
use App\Http\Controllers\ProductCreateController;
use App\Http\Controllers\RicaDocumentController;
use App\Http\Controllers\ServicesController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TroubleTicketController;
use App\Lib\AuthRedirection;
use App\Lib\EnsureBilling;
use App\Lib\Handlers\OrderCreate;
use App\Lib\ProductCreator;
use App\Models\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Shopify\Auth\OAuth;
use Shopify\Auth\Session as AuthSession;
use Shopify\Clients\HttpHeaders;
use Shopify\Clients\Rest;
use Shopify\Context;
use Shopify\Exception\InvalidWebhookException;
use Shopify\Utils;
use Shopify\Webhooks\Registry;
use Shopify\Webhooks\Topics;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
| If you are adding routes outside of the /api path, remember to also add a
| proxy rule for them in web/frontend/vite.config.js
|
*/

Route::fallback(function (Request $request) {
    if (Context::$IS_EMBEDDED_APP &&  $request->query("embedded", false) === "1") {
        if (env('APP_ENV') === 'production') {
            return file_get_contents(public_path('index.html'));
        } else {
            return file_get_contents(base_path('frontend/index.html'));
        }
    } else {
        return redirect(Utils::getEmbeddedAppUrl($request->query("host", null)) . "/" . $request->path());
    }
})->middleware('shopify.installed');

Route::get('/api/auth', function (Request $request) {
    $shop = Utils::sanitizeShopDomain($request->query('shop'));

    // Delete any previously created OAuth sessions that were not completed (don't have an access token)
    Session::where('shop', $shop)->where('access_token', null)->delete();

    return AuthRedirection::redirect($request);
});

Route::get('/api/auth/callback', function (Request $request) {
    $session = OAuth::callback(
        $request->cookie(),
        $request->query(),
        ['App\Lib\CookieHandler', 'saveShopifyCookie'],
    );

    $host = $request->query('host');
    $shop = Utils::sanitizeShopDomain($request->query('shop'));

    $response = Registry::register('/api/webhooks', Topics::APP_UNINSTALLED, $shop, $session->getAccessToken());
    if ($response->isSuccess()) {
        Log::debug("Registered APP_UNINSTALLED webhook for shop $shop");
    } else {
        Log::error(
            "Failed to register APP_UNINSTALLED webhook for shop $shop with response body: " .
                print_r($response->getBody(), true)
        );
    }

    $productUpdateResponse = Registry::register('/api/webhooks', Topics::PRODUCTS_UPDATE, $shop, $session->getAccessToken());
    if ($productUpdateResponse->isSuccess()) {
        Log::debug("Registered PRODUCTS_UPDATE webhook for shop $shop");
    } else {
        Log::error(
            "Failed to register PRODUCTS_UPDATE webhook for shop $shop with response body: " .
                print_r($productUpdateResponse->getBody(), true)
        );
    }

    $productDeleteResponse = Registry::register('/api/webhooks', Topics::PRODUCTS_DELETE, $shop, $session->getAccessToken());
    if ($productDeleteResponse->isSuccess()) {
        Log::debug("Registered PRODUCTS_DELETE webhook for shop $shop");
    } else {
        Log::error(
            "Failed to register PRODUCTS_DELETE webhook for shop $shop with response body: " .
                print_r($productDeleteResponse->getBody(), true)
        );
    }

    $customerCreateResponse = Registry::register('/api/webhooks', Topics::CUSTOMERS_CREATE, $shop, $session->getAccessToken());
    if ($customerCreateResponse->isSuccess()) {
        Log::debug("Registered CUSTOMERS_CREATE webhook for shop $shop");
    } else {
        Log::error(
            "Failed to register CUSTOMERS_CREATE webhook for shop $shop with response body: " .
                print_r($customerCreateResponse->getBody(), true)
        );
    }

    $customerUpdateResponse = Registry::register('/api/webhooks', Topics::CUSTOMERS_UPDATE, $shop, $session->getAccessToken());
    if ($customerUpdateResponse->isSuccess()) {
        Log::debug("Registered CUSTOMERS_UPDATE webhook for shop $shop");
    } else {
        Log::error(
            "Failed to register CUSTOMERS_UPDATE webhook for shop $shop with response body: " .
                print_r($customerUpdateResponse->getBody(), true)
        );
    }

    $customerDeleteResponse = Registry::register('/api/webhooks', Topics::CUSTOMERS_DELETE, $shop, $session->getAccessToken());
    if ($customerDeleteResponse->isSuccess()) {
        Log::debug("Registered CUSTOMERS_DELETE webhook for shop $shop");
    } else {
        Log::error(
            "Failed to register CUSTOMERS_DELETE webhook for shop $shop with response body: " .
                print_r($customerDeleteResponse->getBody(), true)
        );
    }

    $orderCreateResponse = Registry::register('/api/webhooks', Topics::ORDERS_CREATE, $shop, $session->getAccessToken());
    if ($orderCreateResponse->isSuccess()) {
        Log::debug("Registered ORDERS_CREATE webhook for shop $shop");
    } else {
        Log::error(
            "Failed to register ORDERS_CREATE webhook for shop $shop with response body: " .
                print_r($orderCreateResponse->getBody(), true)
        );
    }

    $orderUpdateResponse = Registry::register('/api/webhooks', Topics::ORDERS_UPDATED, $shop, $session->getAccessToken());
    if ($orderUpdateResponse->isSuccess()) {
        Log::debug("Registered ORDERS_UPDATED webhook for shop $shop");
    } else {
        Log::error(
            "Failed to register ORDERS_UPDATED webhook for shop $shop with response body: " .
                print_r($orderUpdateResponse->getBody(), true)
        );
    }

    $orderDeleteResponse = Registry::register('/api/webhooks', Topics::ORDERS_DELETE, $shop, $session->getAccessToken());
    if ($orderDeleteResponse->isSuccess()) {
        Log::debug("Registered ORDERS_DELETE webhook for shop $shop");
    } else {
        Log::error(
            "Failed to register ORDERS_DELETE webhook for shop $shop with response body: " .
                print_r($orderDeleteResponse->getBody(), true)
        );
    }

    $redirectUrl = Utils::getEmbeddedAppUrl($host);
    if (Config::get('shopify.billing.required')) {
        list($hasPayment, $confirmationUrl) = EnsureBilling::check($session, Config::get('shopify.billing'));

        if (!$hasPayment) {
            $redirectUrl = $confirmationUrl;
        }
    }

    return redirect($redirectUrl);
});

Route::get('/api/products/count', function (Request $request) {
    /** @var AuthSession */
    $session = $request->get('shopifySession'); // Provided by the shopify.auth middleware, guaranteed to be active

    $client = new Rest($session->getShop(), $session->getAccessToken());
    $result = $client->get('products/count');

    return response($result->getDecodedBody());
})->middleware('shopify.auth');

Route::get('/api/products/create', function (Request $request) {
    /** @var AuthSession */
    $session = $request->get('shopifySession'); // Provided by the shopify.auth middleware, guaranteed to be active

    $success = $code = $error = null;
    try {
        ProductCreator::call($session, 5);
        $success = true;
        $code = 200;
        $error = null;
    } catch (\Exception $e) {
        $success = false;

        if ($e instanceof ShopifyProductCreatorException) {
            $code = $e->response->getStatusCode();
            $error = $e->response->getDecodedBody();
            if (array_key_exists("errors", $error)) {
                $error = $error["errors"];
            }
        } else {
            $code = 500;
            $error = $e->getMessage();
        }

        Log::error("Failed to create products: $error");
    } finally {
        return response()->json(["success" => $success, "error" => $error], $code);
    }
})->middleware('shopify.auth');

Route::post('/api/webhooks', function (Request $request) {
    try {
        $topic = $request->header(HttpHeaders::X_SHOPIFY_TOPIC, '');

        $response = Registry::process($request->header(), $request->getContent());
        if (!$response->isSuccess()) {
            Log::error("Failed to process '$topic' webhook: {$response->getErrorMessage()}");
            return response()->json(['message' => "Failed to process '$topic' webhook"], 500);
        }
    } catch (InvalidWebhookException $e) {
        Log::error("Got invalid webhook request for topic '$topic': {$e->getMessage()}");
        return response()->json(['message' => "Got invalid webhook request for topic '$topic'"], 401);
    } catch (\Exception $e) {
        Log::error("Got an exception when handling '$topic' webhook: {$e->getMessage()}");
        return response()->json(['message' => "Got an exception when handling '$topic' webhook"], 500);
    }
});
