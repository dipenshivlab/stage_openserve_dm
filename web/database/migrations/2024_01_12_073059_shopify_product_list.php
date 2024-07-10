<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ShopifyProductList extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shopify_product_link', function (Blueprint $table) {
            $table->id();
            $table->string('shop')->nullable();
            $table->foreignId('product_master_id')->constrained('product_master');
            $table->bigInteger('shopify_product_id')->nullable();
            $table->string('shopify_product_name')->nullable();
            $table->string('shopify_product_handle')->nullable();
            $table->string('shopify_product_status')->nullable();
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
        Schema::dropIfExists('shopify_product_link');
    }
}
