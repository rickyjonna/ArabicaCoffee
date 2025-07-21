<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductStockChange extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_stock_change', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id');
            $table->smallInteger('amount_before');
            $table->smallInteger('amount_after');
            $table->smallInteger('minimum_amount_before');
            $table->smallInteger('minimum_amount_after');
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
        Schema::dropIfExists('product_stock_change');
    }
}
