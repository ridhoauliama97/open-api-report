<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SalesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('sales')->delete();

        $faker = fake('id_ID');
        $startDate = Carbon::now()->subMonths(2)->startOfDay();
        $rows = [];

        for ($i = 1; $i <= 100; $i++) {
            $date = $startDate->copy()->addDays(random_int(0, 59));
            $rows[] = [
                'invoice_no' => sprintf('INV-%s-%04d', $date->format('Ymd'), $i),
                'customer_name' => $faker->name(),
                'trx_date' => $date->toDateString(),
                'total' => random_int(150000, 5000000),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('sales')->insert($rows);
    }
}
