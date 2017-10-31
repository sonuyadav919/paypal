<?php namespace Creations\PayPal\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreatePlansTable extends Migration
{

    public function up()
    {
        Schema::create('creations_paypal_plans', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('plan_id');
            $table->string('name');
            $table->string('type');
            $table->string('frequency');
            $table->string('currency');
            $table->integer('amount');
            $table->integer('cycles');
            $table->integer('frequency_interval');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('creations_paypal_plans');
    }

}
