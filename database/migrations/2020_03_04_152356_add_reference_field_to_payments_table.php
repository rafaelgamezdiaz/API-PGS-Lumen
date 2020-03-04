<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddReferenceFieldToPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('reference')->nullable();
        });
        DB::statement("UPDATE payments SET reference = id WHERE id > 0");
        Schema::table('payments', function (Blueprint $table) {
            $table->unique(['account','reference']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
           // $table->dropColumn('reference');
        });
    }
}
