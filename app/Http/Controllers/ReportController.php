<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Report;
use App\Models\Sku;
use App\Models\User;
use App\Models\AdsFee;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $query = Report::query()->with('userInfo');
        $defaultStartDate = now()->startOfMonth();
        $defaultEndDate = now()->endOfMonth();

        $date_start = $defaultStartDate;
        $date_end = $defaultEndDate;

        if ($request->filled('date_range')) {
            $dates = explode(' to ', $request->date_range);
            $date_start = $dates[0] ?? $defaultStartDate;
            $date_end = $dates[1] ?? $dates[0] ?? $defaultEndDate;
        }

        $isFiltering = $request->filled('user_id') || $request->filled('date_range');

        if (auth()->user()->role->name === 'Seller') {
            $userId = auth()->id();
            $report = Report::where('user', $userId)->first();

            if ($isFiltering || !$report) {
                self::calculateReportForUser($userId, null, $date_start, $date_end);
            }

            $query->where('user', $userId);
            $users = collect([auth()->user()]);
        } else {
            $allUsers = User::select('id', 'name')->get();
            $users = collect();

            foreach ($allUsers as $user) {
                $report = Report::where('user', $user->id)->first();

                if ($isFiltering || !$report) {
                    self::calculateReportForUser($user->id, null, $date_start, $date_end);
                    $report = Report::where('user', $user->id)->first(); // cập nhật sau khi tính
                }

                if ($report && $report->unit_sale > 0) {
                    $users->push($user);
                }
            }

            if ($request->filled('user_id')) {
                $query->where('user', $request->user_id);
            } else {
                $query->whereIn('user', $users->pluck('id'));
            }
        }

        // Sắp xếp nếu có yêu cầu
        if ($request->filled('sort')) {
            switch ($request->sort) {
                case 'unit_sale_asc':
                    $query->orderBy('unit_sale', 'asc');
                    break;
                case 'unit_sale_desc':
                    $query->orderBy('unit_sale', 'desc');
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
                case 'profit_asc':
                    $query->orderBy('profit', 'asc');
                    break;
                case 'profit_desc':
                    $query->orderBy('profit', 'desc');
                    break;
            }
        }

        $reports = $query->latest()->paginate(15)->appends($request->all());

        return view('pages.report', compact('reports', 'users', 'date_start', 'date_end'));
    }

    public function updateAds(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:reports,id',
            'ads' => 'required|numeric|min:0',
        ]);

        try {
            $report = Report::findOrFail($request->id);
            $userId = $report->user;

            $this->calculateReportForUser($userId, $request->ads);

            return redirect()->route('report.index')->with('success', 'Cập nhật chi phí Ads và các chỉ số thành công.');
        } catch (\Throwable $e) {
            logger()->error('Lỗi khi cập nhật report: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Đã xảy ra lỗi: ' . $e->getMessage());
        }
    }

    public static function calculateReportForUser(int $userId, ?float $customAds = null, ?string $date_start = null, ?string $date_end = null)
    {
        $orders = Order::where('user_id', $userId);

        if ($date_start) {
            $orders->whereDate('created_at', '>=', $date_start);
        }

        if ($date_end) {
            $orders->whereDate('created_at', '<=', $date_end);
        }

        $orders = $orders->get();

        $ads = self::calculateAds($userId, $customAds, $date_start, $date_end);
        Report::where('user', $userId)->update(['last_calculated_at' => now()]);

        if ($orders->isEmpty()) {
            Report::updateOrCreate(
                ['user' => $userId],
                [
                    'unit_sale' => 0,
                    'revenue' => 0,
                    'base_cost' => 0,
                    'ads' => $ads,
                    'profit' => 0,
                    'bonus' => 0,
                ]
            );
            return;
        }

        $unit_sale = $orders->sum('quantity');
        $revenue = $orders->sum('total');
        $base_cost = 0;
        $total_bonus = 0;
        $ads_per_unit = $unit_sale > 0 ? $ads / $unit_sale : 0;

        foreach ($orders as $order) {
            $sku = Sku::with('tierBonus')->where('sku', $order->sku)->first();

            if ($sku && is_numeric($sku->cost)) {
                $base_cost += $order->quantity * $sku->cost;
            }

            if ($sku && $sku->tierBonus && is_numeric($sku->tierBonus->bonus)) {
                $bonus_pct = $sku->tierBonus->bonus / 100;
                $unit_profit = 0;

                if ($order->quantity > 0) {
                    $unit_profit = (($order->total - ($sku->cost * $order->quantity)) / $order->quantity)
                        - $ads_per_unit
                        - $order->fulfill_fee;
                }

                $total_bonus += $bonus_pct * $order->quantity * $unit_profit;
            }
        }

        Report::updateOrCreate(
            ['user' => $userId],
            [
                'unit_sale' => $unit_sale,
                'revenue' => $revenue,
                'base_cost' => $base_cost,
                'ads' => $ads,
                'profit' => $revenue - $base_cost - $ads,
                'bonus' => $total_bonus,
            ]
        );
    }

    protected static function calculateAds(int $userId, ?float $customAds, ?string $date_start, ?string $date_end)
    {
        if ($customAds !== null) {
            return $customAds;
        }

        if ($date_start && $date_end) {
            return AdsFee::where('user_id', $userId)
                ->whereDate('date', '>=', $date_start)
                ->whereDate('date', '<=', $date_end)
                ->sum('ads');
        }

        return AdsFee::where('user_id', $userId)->sum('ads');
    }
}
