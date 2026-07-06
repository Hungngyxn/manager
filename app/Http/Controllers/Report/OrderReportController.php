<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\ShopUs;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class OrderReportController extends Controller
{
    public function index(Request $request)
    {
        // 1. Lấy danh sách Sellers cho bộ lọc Dropdown (chỉ lấy user có quyền seller)
        // Thay đổi logic roles tùy thuộc vào hệ thống phân quyền của bạn
        $sellers = User::whereHas('shops')->get(['id', 'name']);

        // 2. Xử lý bộ lọc Date Range (Mặc định nếu trống là 7 ngày gần nhất)
        $dateRange = $request->input('date_range');
        if ($dateRange) {
            // Định dạng chuỗi nhận từ flatpickr thường là "Y-m-d to Y-m-d" hoặc "d-m-Y - d-m-Y"
            $dates = explode(' - ', str_replace(' to ', ' - ', $dateRange));
            $startDate = Carbon::parse($dates[0])->startOfDay();
            $endDate = isset($dates[1]) ? Carbon::parse($dates[1])->endOfDay() : Carbon::parse($dates[0])->endOfDay();
        } else {
            $startDate = Carbon::now()->subDays(6)->startOfDay(); // 7 ngày bao gồm hôm nay
            $endDate = Carbon::now()->endOfDay();
        }

        // 3. Khởi tạo Query nền tảng cho phần "Report in 7 days" (hoặc theo Date Range lọc)
        $filteredQuery = ShopUs::whereBetween('created_at', [$startDate, $endDate]);

        // Lọc theo seller nếu có chọn
        if ($request->filled('seller_id')) {
            $filteredQuery->whereHas('shop', function ($q) use ($request) {
                $q->where('user_id', $request->seller_id);
            });
        }

        // Lấy dữ liệu đã lọc về để xử lý tính toán cho biểu đồ và thống kê chặng ngắn
        $filteredOrders = $filteredQuery->get();

        // --- TÍNH TOÁN CHO BỘ THỐNG KÊ FILTER (7 DAYS / CUSTOM RANGE) ---
        $report7Days = [
            'awaiting_shipment'   => $filteredOrders->where('status', 'AWAITING_SHIPMENT')->count(),
            'awaiting_collection' => $filteredOrders->where('status', 'AWAITING_COLLECTION')->count(),
            'in_transit'          => $filteredOrders->where('status', 'IN_TRANSIT')->count(),
            'delivered'           => $filteredOrders->where('status', 'DELIVERED')->count(),
            'completed'           => $filteredOrders->where('status', 'COMPLETED')->count(),
            'canceled'            => $filteredOrders->where('status', 'CANCELED')->count(),
            'total_orders'        => $filteredOrders->count(),
            'units_ordered'       => $filteredOrders->sum(function ($order) {
                // Duyệt qua mảng products được cast tự động để tính tổng số lượng item
                return collect($order->products)->sum('quantity');
            }),
        ];

        // --- XỬ LÝ DỮ LIỆU BIỂU ĐỒ (Group theo từng ngày trong khoảng lọc) ---
        $chartLabels = [];
        $chartData = [
            'awaiting_shipment'   => [],
            'awaiting_collection' => [],
            'in_transit'          => [],
            'delivered'           => [],
            'completed'           => [],
            'canceled'            => []
        ];

        $tmpDate = clone $startDate;
        while ($tmpDate <= $endDate) {
            $dateStr = $tmpDate->format('Y-m-d');
            $chartLabels[] = $tmpDate->format('d/m/Y');

            // Lấy các đơn hàng thuộc ngày cụ thể này
            $dayOrders = $filteredOrders->filter(function ($order) use ($dateStr) {
                return Carbon::parse($order->created_at)->format('Y-m-d') === $dateStr;
            });

            $chartData['awaiting_shipment'][]   = $dayOrders->where('status', 'AWAITING_SHIPMENT')->count();
            $chartData['awaiting_collection'][] = $dayOrders->where('status', 'AWAITING_COLLECTION')->count();
            $chartData['in_transit'][]          = $dayOrders->where('status', 'IN_TRANSIT')->count();
            $chartData['delivered'][]           = $dayOrders->where('status', 'DELIVERED')->count();
            $chartData['completed'][]           = $dayOrders->where('status', 'COMPLETED')->count();
            $chartData['canceled'][]            = $dayOrders->where('status', 'CANCELED')->count();

            $tmpDate->addDay();
        }


        // --- TÍNH TOÁN CHO KHỐI TOÀN BỘ DỮ LIỆU (Section: ALL) ---
        // Khởi tạo query tổng không bị giới hạn bởi date_range ở trên
        $allQuery = ShopUs::query();
        if ($request->filled('seller_id')) {
            $allQuery->whereHas('shop', function ($q) use ($request) {
                $q->where('user_id', $request->seller_id);
            });
        }
        $allOrders = $allQuery->get();

        $reportAll = [
            'awaiting_shipment'   => $allOrders->where('status', 'AWAITING_SHIPMENT')->count(),
            'awaiting_collection' => $allOrders->where('status', 'AWAITING_COLLECTION')->count(),
            'in_transit'          => $allOrders->where('status', 'IN_TRANSIT')->count(),
            'delivered'           => $allOrders->where('status', 'DELIVERED')->count(),
            'completed'           => $allOrders->where('status', 'COMPLETED')->count(),
            'canceled'            => $allOrders->where('status', 'CANCELED')->count(),
            'total_orders'        => $allOrders->count(),
            'units_ordered'       => $allOrders->sum(function ($order) {
                return collect($order->products)->sum('quantity');
            }),
        ];

        return view('pages.report.order', compact(
            'sellers',
            'report7Days',
            'reportAll',
            'chartLabels',
            'chartData',
            'startDate',
            'endDate'
        ));
    }
}