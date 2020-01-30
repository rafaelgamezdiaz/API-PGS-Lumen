<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('payments_types')->insert([
            'id' => 1,
            'type' => 'Enviar Pago'
        ]);
        DB::table('payments_types')->insert([
            'id' => 2,
            'type' => 'Recibir Pago'
        ]);
    }
}
