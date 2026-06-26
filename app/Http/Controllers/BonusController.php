<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BonusController extends Controller
{
    public function index(Request $request)
    {
        $sellers = [
        [
            'name' => 'Seller A',
            'tiers' => [
                ['tier' => 'T1', 'base_cost' => 100, 'refund' => 5, 'fulfill_fee' => 10, 'ads' => 20, 'paid' => 150, 'bonus' => 15],
                ['tier' => 'T2', 'base_cost' => 200, 'refund' => 10, 'fulfill_fee' => 25, 'ads' => 30, 'paid' => 280, 'bonus' => 25],
            ],
        ],
        [
            'name' => 'Seller B',
            'tiers' => [
                ['tier' => 'T1', 'base_cost' => 120, 'refund' => 6, 'fulfill_fee' => 12, 'ads' => 25, 'paid' => 170, 'bonus' => 18],
            ],
        ],
    ];
        return view('pages.bonus', compact('sellers'));
    }

}
