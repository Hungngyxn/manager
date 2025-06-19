<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Order;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $defaultStartDate = now()->subDays(7)->startOfDay();
        $defaultEndDate = now()->endOfDay();
        $startDate = $defaultStartDate;
        $endDate = $defaultEndDate;

        if ($request->filled('date_range')) {
            $dateRange = explode(' to ', $request->input('date_range'));

            if (count($dateRange) === 2) {
                $startDate = Carbon::parse($dateRange[0])->startOfDay();
                $endDate = Carbon::parse($dateRange[1])->endOfDay();
            }
        }

        $from = $request->input('from') ? Carbon::parse($request->input('from'))->startOfDay() : $defaultStartDate;
        $to = $request->input('to') ? Carbon::parse($request->input('to'))->endOfDay() : $defaultEndDate;
        $ordersQuery = Order::whereBetween('created_at', [$startDate, $endDate]);
        $totalOrders = $ordersQuery->count();
        $totalRevenue = $ordersQuery->sum('total');
        $totalCost = $ordersQuery->sum('cost');

        $ordersPerDay = (clone $ordersQuery)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $chartLabels = $ordersPerDay->pluck('date')->toArray();
        $chartData = $ordersPerDay->pluck('count')->toArray();

        // Thống kê theo ngày, tuần, tháng
        $yesterday = Carbon::yesterday();
        $yesterdayOrders = Order::whereDate('created_at', $yesterday)->count();
        $yesterdayRevenue = Order::whereDate('created_at', $yesterday)->sum('total');
        $yesterdayCost = Order::whereDate('created_at', $yesterday)->sum('cost');

        $startOfWeek = Carbon::now()->startOfWeek();
        $thisWeekOrders = Order::whereBetween('created_at', [$startOfWeek, now()->endOfDay()])->count();
        $thisWeekRevenue = Order::whereBetween('created_at', [$startOfWeek, now()->endOfDay()])->sum('total');
        $thisWeekCost = Order::whereBetween('created_at', [$startOfWeek, now()->endOfDay()])->sum('cost');

        $startOfMonth = Carbon::now()->startOfMonth();
        $thisMonthOrders = Order::whereBetween('created_at', [$startOfMonth, now()->endOfDay()])->count();
        $thisMonthRevenue = Order::whereBetween('created_at', [$startOfMonth, now()->endOfDay()])->sum('total');
        $thisMonthCost = Order::whereBetween('created_at', [$startOfMonth, now()->endOfDay()])->sum('cost');

        // Top seller, shop
        $topSellers = $this->fetchTopSellers($from, $to, false); // false vì không phân quyền

        $topShops = Order::selectRaw('shop_name, COUNT(*) as total_orders')
            ->groupBy('shop_name')
            ->orderByDesc('total_orders')
            ->limit(5)
            ->get();

        $totalSellers = User::whereHas('role')->count();

        return view('pages.dashboard', compact(
            'totalSellers',
            'totalOrders',
            'totalRevenue',
            'totalCost',
            'chartLabels',
            'chartData',
            'startDate',
            'endDate',
            'yesterdayOrders',
            'yesterdayRevenue',
            'yesterdayCost',
            'thisWeekOrders',
            'thisWeekRevenue',
            'thisWeekCost',
            'thisMonthOrders',
            'thisMonthRevenue',
            'thisMonthCost',
            'topShops',
            'topSellers',
            'from',
            'to'
        ));
    }


    private function fetchTopSellers($from, $to, $isUserRole)
    {
        if ($isUserRole)
            return collect();

        return DB::table('orders')
            ->join('seller_has_shop', 'orders.shop_name', '=', 'seller_has_shop.shop_name')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->whereBetween('orders.created_at', [
                Carbon::parse($from)->startOfDay(),
                Carbon::parse($to)->endOfDay()
            ])
            ->select(
                'users.name as seller_name',
                DB::raw('SUM(orders.total) as total_revenue'),
                DB::raw('SUM(orders.cost) as total_cost'),
                DB::raw('SUM(orders.total - orders.cost) as profit')
            )
            ->groupBy('users.name')
            ->orderByDesc('profit')
            ->limit(5)
            ->get();
    }

    public function getTopSellers(Request $request)
    {
        $from = Carbon::parse($request->input('from'))->startOfDay();
        $to = Carbon::parse($request->input('to'))->endOfDay();

        $topSellers = DB::table('orders')
            ->join('seller_has_shop', 'orders.shop_name', '=', 'seller_has_shop.shop_name')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->whereBetween('orders.created_at', [$from, $to])
            ->select(
                'users.name as seller_name',
                DB::raw('SUM(orders.total) as total_revenue'),
                DB::raw('SUM(orders.cost) as total_cost'),
                DB::raw('SUM(orders.total - orders.cost) as profit')
            )
            ->groupBy('users.name')
            ->orderByDesc('profit')
            ->limit(5)
            ->get();

        return response()->json($topSellers);
    }
}
