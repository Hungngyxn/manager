<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Report;
use App\Models\Sku;
use App\Models\User;
use App\Models\AdsFee;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $query = Report::query()->with('userInfo');
        $users = User::select('id', 'name')->get();

        $defaultStartDate = now()->startOfMonth();
        $defaultEndDate = now()->endOfMonth();

        $date_start = $defaultStartDate;
        $date_end = $defaultEndDate;

        if ($request->filled('date_range')) {
            $dates = explode(' to ', $request->date_range);
            $date_start = $dates[0] ?? null;
            $date_end = $dates[1] ?? $dates[0];
        }

        if (auth()->user()->role->name === 'Seller') {
            $userId = auth()->id();
            $this->calculateReportForUser($userId, null, $date_start, $date_end);
            $query->where('user', $userId);
            $users = collect([auth()->user()]);
        } else {
            foreach ($users as $user) {
                $this->calculateReportForUser($user->id, null, $date_start, $date_end);
            }

            if ($request->filled('user_id')) {
                $query->where('user', $request->user_id);
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

    protected function calculateReportForUser(int $userId, ?float $customAds = null, ?string $date_start = null, ?string $date_end = null)
    {
        $orders = Order::where('user_id', $userId);

        if ($date_start) {
            $orders->whereDate('created_at', '>=', $date_start);
        }

        if ($date_end) {
            $orders->whereDate('created_at', '<=', $date_end);
        }

        $orders = $orders->get();

        $ads = $this->calculateAds($userId, $customAds, $date_start, $date_end);

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

    protected function calculateAds(int $userId, ?float $customAds, ?string $date_start, ?string $date_end)
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
