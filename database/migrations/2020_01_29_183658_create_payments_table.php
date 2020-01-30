<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Foreign Key
            $table->unsignedBigInteger('payment_type_id');
            $table->foreign('payment_type_id')
                  ->references('id')
                  ->on('payments_types');

            $table->unsignedBigInteger('payment_method_id');
            $table->foreign('payment_method_id')
                ->references('id')
                ->on('payments_methods');

            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('user_id');
            $table->integer('account');
            $table->float('amount');
            $table->float('amount_pending');
            $table->enum('payment_method', ['Tarjeta', 'Efectivo', 'Transferencia']);



            $table->softDeletes();
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
        Schema::dropIfExists('payments');
    }
}
