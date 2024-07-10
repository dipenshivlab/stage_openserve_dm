<?php

declare(strict_types=1);

namespace App\Lib;

use App\Models\Session;
use App\Models\tblShopifyProductList;
use Illuminate\Support\Facades\Log;
use Shopify\Clients\Graphql;
use Shopify\Webhooks\Handler;

class BulkOperationsFinish implements Handler
{
    public function handle(string $topic, string $shop, array $body): void
    {

    }
}
