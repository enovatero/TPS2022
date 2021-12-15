<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToOffers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->tinyInteger('print_awb')->default(0);
            $table->tinyInteger('accesories')->default(0);
            $table->integer('prod_ml')->default(0);
            $table->string('payment_type')->nullable();
            $table->string('distribuitor_order')->nullable();
            $table->string('billing_status')->nullable();
            $table->tinyInteger('listed')->default(0);
            $table->string('attr_p')->nullable();
            $table->string('attr_pjal')->nullable();
            $table->string('attr_pu')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('offers', function (Blueprint $table) {
            //
        });
    }
}
