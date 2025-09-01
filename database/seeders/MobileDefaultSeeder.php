<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MobileDefaultSeeder extends Seeder
{
    public function run()
    {
        // Insert a few default categories if table exists
        if (\Schema::hasTable('categories')) {
            DB::table('categories')->insertOrIgnore([
                ['name' => 'Sales', 'side' => 'income', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Expenses', 'side' => 'expense', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Transport', 'side' => 'expense', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
    }
}
