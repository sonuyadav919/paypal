<?php namespace Creations\PayPal\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreatePaymentsTable extends Migration
{

    public function up()
    {
        Schema::create('creations_paypal_payments', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('payment_id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('payer_id');
            $table->string('phone');
            $table->string('country_code');
            $table->string('currency');
            $table->integer('amount');
            $table->string('plan');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('creations_paypal_payments');
    }

}
