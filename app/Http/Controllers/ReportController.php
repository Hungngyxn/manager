<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\Order;
use App\Models\Sku;
use App\Models\User;
use App\Models\AdsFee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class ReportController extends Controller
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

        if (Report::count() === 0) {
            $userIds = User::pluck('id')->toArray();

            $dates = Order::selectRaw('DATE(created_at) as date')
                ->distinct()
                ->orderBy('date')
                ->pluck('date');

            foreach ($dates as $date) {
                $orders = Order::whereDate('created_at', $date)->get()->groupBy('user_id');

                foreach ($orders as $uid => $userOrders) {
                    if (in_array($uid, $userIds)) {
                        self::aggregateOrders($uid, $date, $userOrders);
                    }
                }
            }
        }

        $query = Report::selectRaw("user, SUM(unit_sale) as unit_sale, SUM(revenue) as revenue, SUM(base_cost) as base_cost, SUM(profit) as profit, SUM(bonus) as bonus")
            ->whereBetween('date', [$startDate, $endDate]);

        if ($userId) {
            $query->where('user', $userId);
        }

        $query = $query->groupBy('user')->with('userInfo');

        if ($sort) {
            switch ($sort) {
                case 'unit_sale_asc':
                    $query->orderBy('unit_sale', 'asc');
                    break;
                case 'unit_sale_desc':
                    $query->orderBy('unit_sale', 'desc');
                    break;
                case 'profit_asc':
                    $query->orderBy('profit', 'asc');
                    break;
                case 'profit_desc':
                    $query->orderBy('profit', 'desc');
                    break;
                case 'revenue_asc':
                    $query->orderBy('revenue', 'asc');
                    break;
                case 'revenue_desc':
                    $query->orderBy('revenue', 'desc');
                    break;
                case 'base_cost_asc':
                    $query->orderBy('base_cost', 'asc');
                    break;
                case 'base_cost_desc':
                    $query->orderBy('base_cost', 'desc');
                    break;
                default:
                    $query->latest('user');
            }
        } else {
            $query->latest('user');
        }

        $reports = $query->get();
        foreach ($reports as $report) {
            $ads = AdsFee::where('user_id', $report->user)
                ->whereBetween('date', [$startDate, $endDate])
                ->sum('ads');
            $report->ads = $ads;

            $report->orders_count = Order::where('user_id', $report->user)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();
        }

        $page = request()->get('page', 1);
        $perPage = 15;
        $offset = ($page - 1) * $perPage;
        $paginatedReports = new LengthAwarePaginator(
            $reports->slice($offset, $perPage)->values(),
            $reports->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        $users = auth()->user()->role->is_super_user
            ? User::whereIn('id', Report::distinct()->pluck('user'))->get()
            : collect([auth()->user()]);

        return view('pages.report', [
            'reports' => $paginatedReports,
            'users' => $users,
            'date_start' => $startDate->format('Y-m-d'),
            'date_end' => $endDate->format('Y-m-d'),
        ]);
    }

    public static function aggregateForDate($userId, $date)
    {
        // ❌ Nếu userId không hợp lệ thì bỏ qua
        if (empty($userId) || !is_numeric($userId)) {
            return;
        }

        $orders = Order::where('user_id', $userId)
            ->whereDate('created_at', $date)
            ->get();

        self::aggregateOrders($userId, $date, $orders);
    }


    public static function aggregateOrders($userId, $date, $orders)
    {
        $unit_sale = $orders->sum('quantity');
        $revenue = $orders->sum('total');
        $base_cost = $orders->sum('cost');
        $total_bonus = 0;

        $total_ads = AdsFee::where('user_id', $userId)
            ->whereDate('date', $date)
            ->sum('ads');
        $ads_per_unit = $unit_sale > 0 ? $total_ads / $unit_sale : 0;

        foreach ($orders as $order) {
            $sku = Sku::with('tierBonus')->where('sku', $order->sku)->first();

            // if ($sku && is_numeric($sku->cost)) {
            //     $base_cost += $order->quantity * $sku->cost;
            // }

            if ($sku && $sku->tierBonus && is_numeric($sku->tierBonus->bonus)) {
                $bonus_pct = $sku->tierBonus->bonus / 100;
                $unit_profit = 0;

                if ($order->quantity > 0) {
                    $unit_profit = (($order->total - ($sku->cost * $order->quantity)) / $order->quantity)
                        - $order->fulfill_fee
                        - $ads_per_unit;
                }

                if ($sku->tierBonus->tier != 'Tier 1') {
                    $unit_profit *= 1.5;
                }
                $total_bonus += $bonus_pct * $order->quantity * $unit_profit;
            }
        }

        Report::updateOrCreate(
            ['user' => $userId, 'date' => $date],
            [
                'unit_sale' => $unit_sale,
                'revenue' => $revenue,
                'base_cost' => $base_cost,
                'profit' => $revenue - $base_cost,
                'bonus' => $total_bonus,
                'last_calculated_at' => now(),
            ]
        );
    }

    public static function calculateReportForUser($userId, $shopName = null, $startDate, $endDate)
    {
        $dates = Order::where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date')
            ->distinct()
            ->pluck('date');

        foreach ($dates as $date) {
            $orders = Order::where('user_id', $userId)
                ->whereDate('created_at', $date);

            if ($shopName) {
                $orders->where('shop_name', $shopName);
            }

            $orders = $orders->get();

            self::aggregateOrders($userId, $date, $orders);
        }
    }
}
