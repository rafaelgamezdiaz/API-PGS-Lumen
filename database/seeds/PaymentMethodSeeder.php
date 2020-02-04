<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('methods')->insert([
            'id' => 1,
            'method' => 'Transferencia'
        ]);
        DB::table('methods')->insert([
            'id' => 2,
            'method' => 'Efectivo'
        ]);
        DB::table('methods')->insert([
            'id' => 3,
            'method' => 'Cheque'
        ]);
        DB::table('methods')->insert([
            'id' => 4,
            'method' => 'Pago Electrónico'
        ]);

    }
}
