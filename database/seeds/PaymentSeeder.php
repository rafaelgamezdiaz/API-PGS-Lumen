<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('payments')->insert(['id' => 1,'type_id' => 2,'method_id' => 3,'client_id' => 5,'username' => 'aguaseo','account' => 8,'amount' => 300.0,'amount_pending' => 300.0]);
        DB::table('payments')->insert(['id' => 2,'type_id' => 1,'method_id' => 2,'client_id' => 23,'username' => 'aguaseo','account' => 8,'amount' => 400.0,'amount_pending' => 400.0]);
        DB::table('payments')->insert(['id' => 3,'type_id' => 2,'method_id' => 3,'client_id' => 5,'username' => 'gamezr@zippyttech.com','account' => 35,'amount' => 30.0,'amount_pending' => 30.0]);
        DB::table('payments')->insert(['id' => 4,'type_id' => 1,'method_id' => 1,'client_id' => 23,'username' => 'aguaseo','account' => 8,'amount'=> 67.0,'amount_pending' => 67.0]);
        DB::table('payments')->insert(['id' => 5,'type_id' => 2,'method_id' => 2,'client_id' => 60,'username' => 'aguaseo','account' => 8,'amount'=> 32.0,'amount_pending' => 32.0]);
        DB::table('payments')->insert(['id' => 6,'type_id' => 1,'method_id' => 4,'client_id' => 93,'username' => 'aguaseo','account' => 8,'amount'=> 200.0,'amount_pending' => 200.0]);
        DB::table('payments')->insert(['id' => 7,'type_id' => 2,'method_id' => 2,'client_id' => 23,'username' => 'gamezr@zippyttech.com','account' => 35,'amount'=> 470.0,'amount_pending' => 470.0]);
        DB::table('payments')->insert(['id' => 8,'type_id' => 1,'method_id' => 4,'client_id' => 67,'username' => 'aguaseo','account' => 8,'amount'=> 678.0,'amount_pending' => 678.0]);
        DB::table('payments')->insert(['id' => 9,'type_id' => 1,'method_id' => 4,'client_id' => 237,'username' => 'aguaseo','account' => 8,'amount'=> 13.0,'amount_pending' => 13.0]);
        DB::table('payments')->insert(['id' => 10,'type_id' => 2,'method_id' => 2,'client_id' => 237,'username' => 'aguaseo','account' => 8,'amount'=> 54.0,'amount_pending' => 54.0]);
        DB::table('payments')->insert(['id' => 11,'type_id' => 2,'method_id' => 1,'client_id' => 14,'username' => 'aguaseo','account' => 8,'amount'=> 21.0,'amount_pending' => 21.0]);
        DB::table('payments')->insert(['id' => 12,'type_id' => 1,'method_id' => 2,'client_id' => 23,'username' => 'aguaseo','account' => 8,'amount'=> 40.0,'amount_pending' => 40.0]);
        DB::table('payments')->insert(['id' => 13,'type_id' => 1,'method_id' => 1,'client_id' => 23,'username' => 'aguaseo','account' => 8,'amount'=> 23.0,'amount_pending' => 23.0]);
        DB::table('payments')->insert(['id' => 14,'type_id' => 1,'method_id' => 2,'client_id' => 14,'username' => 'aguaseo','account' => 8,'amount'=> 40.0,'amount_pending' => 40.0]);
        DB::table('payments')->insert(['id' => 15,'type_id' => 2,'method_id' => 3,'client_id' => 21,'username' => 'aguaseo','account' => 8,'amount'=> 11.0,'amount_pending' => 11.0]);
        DB::table('payments')->insert(['id' => 16,'type_id' => 2,'method_id' => 2,'client_id' => 23,'username' => 'aguaseo','account' => 8,'amount'=> 111.0,'amount_pending' => 111.0]);
        DB::table('payments')->insert(['id' => 17,'type_id' => 2,'method_id' => 4,'client_id' => 25,'username' => 'gamezr@zippyttech.com','account' => 35,'amount'=> 222.0,'amount_pending' => 222.0]);
        DB::table('payments')->insert(['id' => 18,'type_id' => 1,'method_id' => 4,'client_id' => 73,'username' => 'aguaseo','account' => 8,'amount'=> 222.0,'amount_pending' => 222.0]);
        DB::table('payments')->insert(['id' => 19,'type_id' => 1,'method_id' => 2,'client_id' => 33,'username' => 'aguaseo','account' => 8,'amount'=> 76.0,'amount_pending' => 76.0]);
        DB::table('payments')->insert(['id' => 20,'type_id' => 2,'method_id' => 2,'client_id' => 11,'username' => 'aguaseo','account' => 8,'amount'=> 34.0,'amount_pending' => 34.0]);
        DB::table('payments')->insert(['id' => 21,'type_id' => 1,'method_id' => 1,'client_id' => 23,'username' => 'gamezr@zippyttech.com','account' => 35,'amount'=> 21.0,'amount_pending' => 21.0]);
    }
}
