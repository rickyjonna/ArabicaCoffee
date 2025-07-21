<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIngredientStockChange extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ingredient_stock_change', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id');
            $table->smallInteger('amount_before');
            $table->smallInteger('amount_after');
            $table->smallInteger('minimum_amount_before');
            $table->smallInteger('minimum_amount_after');
            $table->date('expired_at_before');
            $table->date('expired_at_after');
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
        Schema::dropIfExists('ingredient_stock_change');
    }
}
