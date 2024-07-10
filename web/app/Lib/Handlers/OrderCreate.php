<?php

declare(strict_types=1);

namespace App\Lib\Handlers;

use App\Jobs\CreateOrderJob;
use Shopify\Webhooks\Handler;
use Illuminate\Support\Facades\Log;

class OrderCreate implements Handler
{

  public function handle(string $topic, string $shop, array $body): void
  {
     }
}
