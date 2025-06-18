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
        $user = auth()->user();
        $isUserRole = $user->role->name === 'Seller';

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

        if ($isUserRole) {
            $ordersQuery->whereIn('shop_name', function ($query) use ($user) {
                $query->select('shop_name')
                    ->from('seller_has_shop')
                    ->where('user_id', $user->id);
            });
        }

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

        $shopNames = $isUserRole
            ? DB::table('seller_has_shop')->where('user_id', $user->id)->pluck('shop_name')
            : null;

        $filterByShops = fn($query) => $isUserRole
            ? $query->whereIn('shop_name', $shopNames)
            : $query;

        $yesterday = Carbon::yesterday();
        $yesterdayOrders = $filterByShops(Order::whereDate('created_at', $yesterday))->count();
        $yesterdayRevenue = $filterByShops(Order::whereDate('created_at', $yesterday))->sum('total');
        $yesterdayCost = $filterByShops(Order::whereDate('created_at', $yesterday))->sum('cost');

        $startOfWeek = Carbon::now()->startOfWeek();
        $thisWeekOrders = $filterByShops(Order::whereBetween('created_at', [$startOfWeek, now()->endOfDay()]))->count();
        $thisWeekRevenue = $filterByShops(Order::whereBetween('created_at', [$startOfWeek, now()->endOfDay()]))->sum('total');
        $thisWeekCost = $filterByShops(Order::whereBetween('created_at', [$startOfWeek, now()->endOfDay()]))->sum('cost');

        $startOfMonth = Carbon::now()->startOfMonth();
        $thisMonthOrders = $filterByShops(Order::whereBetween('created_at', [$startOfMonth, now()->endOfDay()]))->count();
        $thisMonthRevenue = $filterByShops(Order::whereBetween('created_at', [$startOfMonth, now()->endOfDay()]))->sum('total');
        $thisMonthCost = $filterByShops(Order::whereBetween('created_at', [$startOfMonth, now()->endOfDay()]))->sum('cost');

        $topSellers = $this->fetchTopSellers($from, $to, $isUserRole);

        $topShops = Order::selectRaw('shop_name, COUNT(*) as total_orders')
            ->when($isUserRole, function ($query) use ($shopNames) {
                return $query->whereIn('shop_name', $shopNames);
            })
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
            'to',
            'isUserRole'
        ));
    }

    private function fetchTopSellers($from, $to, $isUserRole)
    {
        if ($isUserRole) return collect();

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
