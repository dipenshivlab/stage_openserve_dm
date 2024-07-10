<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ProductMaster extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_master', function (Blueprint $table) {
            $table->id();
            $table->string('product_code')->nullable();
            $table->string('product_name')->nullable();
            $table->string('product_category')->nullable();
            $table->integer('download_speed')->nullable();
            $table->integer('upload_speed')->nullable();
            $table->string('contract_period')->nullable();
            $table->float('price_PM')->nullable();
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
        Schema::dropIfExists('product_master');
    }
}
