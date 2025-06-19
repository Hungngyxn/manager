<?php

namespace Database\Factories;

use App\Models\Access;
use App\Models\Menu;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccessFactory extends Factory
{
    protected $model = Access::class;

    public function definition()
    {
        return [
            'role_id' => Role::factory(),
            'menu_id' => Menu::factory(),
            'status' => 0,
        ];
    }

    public function forAdmin($menuId)
    {
        $admin = Role::firstOrCreate(['name' => 'Administrator']);

        return $this->state(function () use ($admin, $menuId) {
            return [
                'role_id' => $admin->id,
                'menu_id' => $menuId,
                'status' => 2,
            ];
        });
    }

    public function forSeller($menuName)
    {
        $seller = Role::firstOrCreate(['name' => 'Seller']);
        $menu = Menu::where('name', $menuName)->first();

        // Logic status cho seller
        $status = match (true) {
            in_array($menuName, ['dashboard', 'order', 'shop', 'account']) => 2,
            in_array($menuName, ['sku', 'report']) => 1,
            default => 0,
        };

        return $this->state(function () use ($seller, $menu, $status) {
            return [
                'role_id' => $seller->id,
                'menu_id' => $menu->id,
                'status' => $status,
            ];
        });
    }
}
