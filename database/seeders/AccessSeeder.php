<?php

namespace Database\Seeders;

use App\Models\Access;
use App\Models\Menu;
use Illuminate\Database\Seeder;

class AccessSeeder extends Seeder
{
    public function run()
    {
        $menuNames = ['dashboard', 'order', 'shop', 'user', 'role', 'team', 'sku', 'report', 'account'];

        foreach ($menuNames as $name) {
            Menu::firstOrCreate(['name' => $name]);
        }

        $menus = Menu::all();

        // Tạo quyền cho Admin
        foreach ($menus as $menu) {
            Access::factory()->forAdmin($menu->id)->create();
        }

        // Tạo quyền cho Seller
        foreach ($menus as $menu) {
            Access::factory()->forSeller($menu->name)->create();
        }
    }
}
