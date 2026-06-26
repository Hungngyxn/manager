<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SellerDailyReport;
use App\Models\SellerMonthlyExpense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class ReportSellerController extends Controller
{
    public function index(Request $request)
    {
        set_time_limit(300);

        $dateRange = $request->input('date_range');
        $sort = $request->input('sort');
        $userId = $request->input('user_id');

        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        if ($dateRange) {
            [$start, $end] = explode(' to ', $dateRange);
            $startDate = Carbon::parse($start)->startOfDay();
            $endDate = Carbon::parse($end)->endOfDay();
        }

        if (auth()->user()->role->is_super_user !== 1) {
            $userId = auth()->id();
        }

        if (SellerDailyReport::count() === 0) {
            $this->initDailyReports();
        }

        $query = SellerDailyReport::query()
            ->select('seller_id')
            ->selectRaw("SUM(total_orders) as orders_count")
            ->selectRaw("SUM(unit_sale) as unit_sale")
            ->selectRaw("SUM(total_revenue) as revenue")
            ->selectRaw("SUM(total_base_cost) as base_cost")
            ->whereBetween('report_date', [$startDate, $endDate]);

        if ($userId) {
            $query->where('seller_id', $userId);
        }

        $reports = $query->groupBy('seller_id')->with('seller')->get();

        $monthsInFilter = [];
        $current = $startDate->copy()->startOfMonth();
        while ($current->lessThanOrEqualTo($endDate)) {
            $monthsInFilter[] = ['month' => $current->month, 'year' => $current->year];
            $current->addMonth();
        }

        foreach ($reports as $report) {
            $report->user = $report->seller_id;
            $report->userInfo = $report->seller;

            $expenses = SellerMonthlyExpense::query()
                ->where('seller_id', $report->seller_id)
                ->where(function ($q) use ($monthsInFilter) {
                    foreach ($monthsInFilter as $item) {
                        $q->orWhere(function ($subQ) use ($item) {
                            $subQ->where('month', $item['month'])->where('year', $item['year']);
                        });
                    }
                })
                ->selectRaw("SUM(ads_cost) as total_ads, SUM(proxy_cost) as total_proxy, SUM(design_cost) as total_design, SUM(account_cost) as total_account")
                ->first();

            $report->ads = $expenses->total_ads ?? 0.00;
            $report->proxy = $expenses->total_proxy ?? 0.00;
            $report->design = $expenses->total_design ?? 0.00;
            $report->account = $expenses->total_account ?? 0.00;

            $report->gross_profit = $report->revenue - $report->base_cost;
            $report->profit = $report->revenue - $report->base_cost - $report->ads - $report->proxy - $report->design - $report->account;
        }

        $reports = $sort ? $this->sortReports($reports, $sort) : $reports->sortByDesc('user');

        $page = request()->get('page', 1);
        $perPage = 15;
        $paginatedReports = new LengthAwarePaginator(
            $reports->slice(($page - 1) * $perPage, $perPage)->values(),
            $reports->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        $users = auth()->user()->role->is_super_user
            ? User::whereIn('id', SellerDailyReport::distinct()->pluck('seller_id'))->get()
            : collect([auth()->user()]);

        return view('pages.report.seller', [
            'reports' => $paginatedReports,
            'users' => $users,
            'date_start' => $startDate->format('Y-m-d'),
            'date_end' => $endDate->format('Y-m-d'),
        ]);
    }

    public function initDailyReports()
    {
        $shopMap = DB::table('seller_has_shop')
            ->whereNotNull('shop_code')
            ->pluck('user_id', 'shop_code')
            ->toArray();

        $orders = DB::table('shop_us')
            ->select('shop_code', 'created_at', 'products')
            ->get();

        $memoryData = [];

        // 1. Duyệt qua các đơn hàng để gom nhóm và tính tổng trên RAM
        foreach ($orders as $order) {
            $sellerId = $shopMap[$order->shop_code] ?? null;
            $date = $order->created_at ? Carbon::parse($order->created_at)->format('Y-m-d') : null;

            if (empty($sellerId) || empty($date) || empty($order->products)) {
                continue;
            }

            $productss = json_decode($order->products, true);
            if (!is_array($productss)) {
                continue;
            }

            $orderUnitSale = 0;
            $orderTotalRevenue = 0;
            foreach ($productss as $prod) {
                $orderUnitSale += (int) ($prod['quantity'] ?? 0);
                $orderTotalRevenue += (float) ($prod['total_price'] ?? 0);
            }

            // Tạo key gộp theo Seller và Ngày
            $key = $sellerId . '_' . $date;

            if (!isset($memoryData[$key])) {
                $memoryData[$key] = [
                    'seller_id' => $sellerId,
                    'report_date' => $date,
                    'total_orders' => 0,
                    'unit_sale' => 0,
                    'total_revenue' => 0,
                    'total_base_cost' => 0,
                ];
            }

            // Cộng dồn tích lũy trên RAM
            $memoryData[$key]['total_orders'] += 1;
            $memoryData[$key]['unit_sale'] += $orderUnitSale;
            $memoryData[$key]['total_revenue'] += $orderTotalRevenue;
            $memoryData[$key]['total_base_cost'] += (float) ($order->cost ?? 0);
        }

        // 2. Ghi dữ liệu đã nén vào Database (Đúng 1 dòng duy nhất cho 1 ngày của Seller)
        foreach ($memoryData as $data) {
            SellerDailyReport::updateOrCreate(
                [
                    'seller_id' => $data['seller_id'],
                    'report_date' => $data['report_date']
                ],
                [
                    'total_orders' => $data['total_orders'],
                    'unit_sale' => $data['unit_sale'],
                    'total_revenue' => $data['total_revenue'],
                    'total_base_cost' => $data['total_base_cost'],
                    'last_calculated_at' => now(),
                ]
            );
        }
    }

    private function sortReports($reports, $sort)
    {
        switch ($sort) {
            case 'unit_sale_asc':
                return $reports->sortBy('unit_sale');
            case 'unit_sale_desc':
                return $reports->sortByDesc('unit_sale');
            case 'revenue_asc':
                return $reports->sortBy('revenue');
            case 'revenue_desc':
                return $reports->sortByDesc('revenue');
            case 'base_cost_asc':
                return $reports->sortBy('base_cost');
            case 'base_cost_desc':
                return $reports->sortByDesc('base_cost');
            case 'profit_asc':
                return $reports->sortBy('profit');
            case 'profit_desc':
                return $reports->sortByDesc('profit');
            default:
                return $reports->sortByDesc('user');
        }
    }
}