<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\ShopUs;
use App\Models\User;
use App\Models\SellerHasShop;
use App\Models\TiktokPayout;
use Illuminate\Http\Request;
use Carbon\Carbon;

class FinanceReportController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // Kiểm tra quyền Super User theo cấu trúc hệ thống của bạn
        if ($user && isset($user->role) && $user->role->is_super_user == 1) {
            $sellers = User::whereHas('shops')->get(['id', 'name']);
            $shopCodes = SellerHasShop::pluck('shop_code')->filter()->unique()->toArray();
        } else {
            $sellers = User::where('id', $user->id)->get(['id', 'name']);
            $shopCodes = SellerHasShop::where('team_id', 10)
                ->where('user_id', $user->id)
                ->pluck('shop_code')
                ->filter()
                ->unique()
                ->toArray();
        }

        if ($request->filled('shop_code')) {
            $filteredShopCodes = [$request->shop_code];
        } else {
            $filteredShopCodes = $shopCodes;
        }

        // 2. Xử lý khoảng thời gian (Mặc định 7 ngày gần nhất)
        $dateRange = $request->input('date_range');
        if ($dateRange) {
            $dates = explode(' - ', str_replace(' to ', ' - ', $dateRange));
            $startDate = Carbon::parse($dates[0])->startOfDay();
            $endDate = isset($dates[1]) ? Carbon::parse($dates[1])->endOfDay() : Carbon::parse($dates[0])->endOfDay();
        } else {
            $startDate = Carbon::now()->subDays(6)->startOfDay();
            $endDate = Carbon::now()->endOfDay();
        }

        // 3. Query nền tảng (Áp dụng phân quyền shop_code)
        $baseOrderQuery = ShopUs::whereIn('shop_code', $filteredShopCodes);
        $basePayoutQuery = TiktokPayout::whereIn('shop_code', $filteredShopCodes);

        if ($request->filled('seller_id')) {
            $sellerShops = SellerHasShop::where('user_id', $request->seller_id)->pluck('shop_code')->toArray();
            $baseOrderQuery->whereIn('shop_code', $sellerShops);
            $basePayoutQuery->whereIn('shop_code', $sellerShops);
        }

        // Lấy dữ liệu Orders & Payouts theo khoảng thời gian và toàn bộ
        $filteredOrders = (clone $baseOrderQuery)->whereBetween('created_at', [$startDate, $endDate])->get();
        $allOrders = (clone $baseOrderQuery)->get();

        $filteredPayouts = (clone $basePayoutQuery)->whereBetween('payout_initiation_date', [$startDate, $endDate])->get();
        $allPayouts = (clone $basePayoutQuery)->get();

        // --- REPORT IN RANGE ---
        $payoutRangeAmount = $filteredPayouts->sum('settlement_amount');
        $reportRange = $this->calculateFinanceMetrics($filteredOrders, $payoutRangeAmount);

        // --- XỬ LÝ KHỐI DỮ LIỆU BIỂU ĐỒ ---
        $chartLabels = [];
        $chartData = [
            'total' => [],
            'fulfill' => [],
            'basecost' => [],
            'design_fee' => [],
            'refunds' => [],
            'payouts' => [],
            'profits' => []
        ];

        $tmpDate = clone $startDate;
        while ($tmpDate <= $endDate) {
            $dateStr = $tmpDate->format('Y-m-d');
            $chartLabels[] = $tmpDate->format('d/m/Y');

            $dayOrders = $filteredOrders->filter(function ($order) use ($dateStr) {
                return Carbon::parse($order->created_at)->format('Y-m-d') === $dateStr;
            });

            $dayPayoutAmount = $filteredPayouts->filter(function ($payout) use ($dateStr) {
                return Carbon::parse($payout->payout_initiation_date)->format('Y-m-d') === $dateStr;
            })->sum('settlement_amount');

            $dayMetrics = $this->calculateFinanceMetrics($dayOrders, $dayPayoutAmount);

            $chartData['total'][] = $dayMetrics['total'];
            $chartData['fulfill'][] = $dayMetrics['fulfill_fee'];
            $chartData['basecost'][] = $dayMetrics['basecost'];
            $chartData['design_fee'][] = $dayMetrics['design_fee'];
            $chartData['refunds'][] = $dayMetrics['refunds'];
            $chartData['payouts'][] = $dayMetrics['payouts'];
            $chartData['profits'][] = $dayMetrics['profits'];

            $tmpDate->addDay();
        }

        // --- REPORT ALL ---
        $payoutAllAmount = $allPayouts->sum('settlement_amount');
        $reportAll = $this->calculateFinanceMetrics($allOrders, $payoutAllAmount);

        $shops = $shopCodes;

        return view('pages.report.finance', compact(
            'sellers',
            'shops',
            'reportRange',
            'reportAll',
            'chartLabels',
            'chartData',
            'startDate',
            'endDate'
        ));
    }

    private function calculateFinanceMetrics($orders, $payoutAmount = 0)
    {
        $total = $orders->sum('total_amount');

        $fulfillFee = 0;
        $sellerShipping = 0;
        $labelFee = 0;
        $designFee = 0;
        $adsFee = 0;
        $basecost = 0;

        foreach ($orders as $order) {
            $products = collect($order->products);

            foreach ($products as $prod) {
                $quantity = $prod['quantity'] ?? 1;

                $fulfillFee += ($prod['fulfill_fee'] ?? 0) * $quantity;
                $sellerShipping += ($prod['seller_shipping'] ?? 0) * $quantity;
                $labelFee += ($prod['label_fee'] ?? 0) * $quantity;
                $designFee += ($prod['design_fee'] ?? 0) * $quantity;
                $adsFee += ($prod['ads_fee'] ?? 0) * $quantity;
                $basecost += ($prod['base_cost'] ?? 0);
            }
        }

        $refunds = $orders->where('status', 'REFUNDED')->sum('total_amount');

        $profits = $total - ($fulfillFee + $sellerShipping + $labelFee + $designFee + $adsFee + $basecost);

        return [
            'total' => $total,
            'fulfill_fee' => $fulfillFee,
            'basecost' => $basecost,
            'seller_ship' => $sellerShipping,
            'label_fee' => $labelFee,
            'design_fee' => $designFee,
            'ads' => $adsFee,
            'refunds' => $refunds,
            'profits' => $profits,
            'payouts' => $payoutAmount
        ];
    }
}