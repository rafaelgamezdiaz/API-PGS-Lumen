<?php

use App\Models\Payment;
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
            $table->unsignedBigInteger('type_id');
            $table->foreign('type_id')
                  ->references('id')
                  ->on('types');

            $table->unsignedBigInteger('method_id');
            $table->foreign('method_id')
                ->references('id')
                ->on('methods');

            $table->unsignedBigInteger('client_id');
            $table->string('username');
            $table->integer('account');
            $table->float('amount');
            $table->float('amount_pending');
            $table->string('status')->default(Payment::PAYMENT_STATUS_AVAILABLE);

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
