<?php

declare(strict_types=1);

namespace App\Lib\Handlers;

use App\Models\ShopifyCustomer;
use App\Models\tblShopifyProductList;
use Illuminate\Support\Facades\Log;
use Shopify\Webhooks\Handler;

class CustomerDelete implements Handler
{
    public function handle(string $topic, string $shop, array $body): void
    {

    }
}
