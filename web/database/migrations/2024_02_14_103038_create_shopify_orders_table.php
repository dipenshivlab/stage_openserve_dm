<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopifyOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shopify_orders', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('shopify_customer_id');
            $table->bigInteger('order_id')->unique();
            $table->string('order_number')->nullable();
            $table->string('product_id')->nullable();
            $table->string('variant_id')->nullable();
            $table->string('openserve_order_id')->nullable();
            $table->string('openserve_reason')->nullable();
            $table->foreign('shopify_customer_id')->references('customer_id')->on('shopify_customers');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shopify_orders');
    }
}
