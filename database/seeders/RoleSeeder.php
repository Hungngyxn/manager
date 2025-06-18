<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;'); // Tắt kiểm tra khóa ngoại

        Role::truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        // Tạo role Administrator
        Role::factory()->admin()->create();

        // Tạo role User
        Role::factory()->seller()->create();
    }
}
