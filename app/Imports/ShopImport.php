<?php

namespace App\Imports;

use App\Models\Order;
use App\Models\SellerHasShop;
use App\Models\ShopAccount;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Http\Controllers\ReportController;

class ShopImport implements ToCollection, WithHeadingRow
{
    public $created = [];
    public $skipped = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $shopName = trim($row['shop_name'] ?? '');
            $shopCode = trim($row['shop_code'] ?? '');
            $sellerName = trim($row['seller_name'] ?? '');
            $email = trim($row['email'] ?? '');

            if (empty($shopName)) {
                $this->skipped[] = 'Missing shop_name';
                continue;
            }

            $user = User::where('name', $sellerName)->first();

            $existingShop = SellerHasShop::where('shop_name', $shopName)->first();

            if ($existingShop) {
                $oldUserId = $existingShop->user_id;

                if (is_null($oldUserId) && $user?->id) {
                    $existingShop->update([
                        'user_id' => $user->id,
                        'shop_code' => $shopCode,
                        'email' => $email ?: null,
                    ]);

                    Order::where('shop_name', $shopName)
                        ->update(['user_id' => $user->id]);

                    // Tính lại report cho các ngày có đơn cũ
                    $dates = Order::where('shop_name', $shopName)
                        ->pluck('created_at')
                        ->map(fn($dt) => $dt->toDateString())
                        ->unique();

                    foreach ($dates as $date) {
                        ReportController::aggregateForDate($user->id, $date);
                    }

                    $this->created[] = "$shopName (assigned to: $sellerName, reports updated)";
                } else {
                    $this->skipped[] = "$shopName (already exists with seller)";
                }
            } else {
                SellerHasShop::create([
                    'shop_name' => $shopName,
                    'shop_code' => $shopCode,
                    'user_id' => $user?->id,
                    'email' => $email ?: null,
                ]);

                $this->created[] = "$shopName (new, seller: " . ($sellerName ?: 'Unassigned') . ")";
            }

            if ($email) {
                ShopAccount::updateOrCreate(
                    ['email' => $email],
                    [
                        'thang_reg' => now()->startOfMonth()->toDateString(),
                        'tuoi_acc' => 0,
                        'email' => $email,
                        'user_id' => $user?->id,
                        'status' => 'active',
                    ]
                );
            }
        }
    }
}
