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
        DB::table('types')->insert([
            'id' => 1,
            'type' => 'A Enviar'
        ]);
        DB::table('types')->insert([
            'id' => 2,
            'type' => 'A Recibir'
        ]);
    }
}
