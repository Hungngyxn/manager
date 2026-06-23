<?php

namespace App\Exports;

use App\Models\SellerHasShop;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ShopBalanceExport implements FromCollection, WithHeadings
{
    protected $request;
    protected $user;

    public function __construct(Request $request, $user)
    {
        $this->request = $request;
        $this->user = $user;
    }

    public function collection()
    {
        $query = SellerHasShop::with('seller');

        // 🔁 reuse filter giống index
        if ($this->user->role->name === 'Seller') {
            $query->where('user_id', $this->user->id);
        }

        if ($this->request->filled('user_id') && $this->user->role->name !== 'Seller') {
            if ($this->request->user_id === 'null') {
                $query->whereNull('user_id');
            } else {
                $query->where('user_id', $this->request->user_id);
            }
        }

        if ($this->request->filled('team_id')) {
            if ($this->request->team_id === 'null') {
                $query->whereNull('team_id');
            } else {
                $query->where('team_id', $this->request->team_id);
            }
        }

        if ($this->request->filled('search')) {
            $search = $this->request->search;
            $query->where(function ($q) use ($search) {
                $q->where('shop_name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('shop_code', 'like', '%' . $search . '%');
            });
        }

        if ($this->request->filled('filter_pending_nullbank') && $this->request->filter_pending_nullbank == 1) {
            $query->where(function ($q) {
                $q->where('pending', '>', 0)
                    ->orWhere('onhold', '>', 0);
            })->where('bank', '=', '-');
        }

        return $query->get()->map(function ($shop) {
            return [
                $shop->shop_name,
                $shop->shop_code,
                $shop->email,
                optional($shop->seller)->name,
                $shop->pending,
                $shop->onhold,
                $shop->payout,
                $shop->bank,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Shop Name',
            'Shop Code',
            'Email',
            'Seller',
            'Pending',
            'Onhold',
            'Payout',
            'Bank',
        ];
    }
}