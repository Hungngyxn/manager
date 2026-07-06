<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\SellerHasShop;
use App\Models\TiktokStatement;
use App\Services\TikTokService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TiktokStatementController extends Controller
{
    protected $tiktok;

    public function __construct(TikTokService $tiktok)
    {
        $this->tiktok = $tiktok;
    }

    public function index(Request $request)
    {
        $user = auth()->user()->role->is_super_user;

        // Kiểm tra nếu là Admin (Thay thế 'admin' bằng hàm check quyền thực tế của bạn nếu khác)
        if ($user && $user == 1) {
            // Admin thấy tất cả shop codes đang hoạt động trong hệ thống
            $shopCodes = SellerHasShop::pluck('shop_code')
                ->filter()
                ->unique()
                ->toArray();

            $query = TiktokStatement::query();
        } else {
            // Seller thường chỉ thấy shop thuộc user và team 10
            $shopCodes = SellerHasShop::where('team_id', 10)
                ->where('user_id', $user->id)
                ->pluck('shop_code')
                ->filter()
                ->unique()
                ->toArray();

            $query = TiktokStatement::whereIn('shop_code', $shopCodes);
        }

        if ($request->filled('shop_code')) {
            $query->where('shop_code', $request->shop_code);
        }

        if ($request->filled('payout_id')) {
            $query->where('payout_id', 'like', '%' . $request->payout_id . '%');
        }

        if ($request->filled('statement_id')) {
            $query->where('statement_id', 'like', '%' . $request->statement_id . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('statement_date', '>=', Carbon::parse($request->from_date));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('statement_date', '<=', Carbon::parse($request->to_date));
        }

        $sortField = $request->get('sort', 'statement_date');
        $sortDirection = strtolower($request->get('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        $allowedSortFields = [
            'statement_date',
            'settlement_amount',
            'net_sales',
            'shipping',
            'fee',
            'adjustment'
        ];

        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'statement_date';
        }

        $statements = $query->orderBy($sortField, $sortDirection)
            ->paginate(20)
            ->withQueryString();

        return view('pages.finance.statement', compact('statements', 'shopCodes'));
    }

    public function syncStatements(Request $request)
    {
        $user = auth()->user()->role->is_super_user;

        // Kiểm tra nếu là Admin (Thay thế 'admin' bằng hàm check quyền thực tế của bạn nếu khác)
        if ($user && $user == 1) {
            $shops = SellerHasShop::where('team_id', 10)->get();
        } else {
            $shops = SellerHasShop::where('team_id', 10)
                ->where('user_id', $user->id)
                ->get();
        }

        if ($shops->isEmpty()) {
            return redirect()->back()->with('warning', 'Không tìm thấy shop nào để đồng bộ.');
        }

        $totalSyncCount = 0;

        foreach ($shops as $shop) {
            try {
                $token = $this->tiktok->getAccessToken($shop);

                if (!$token) {
                    Log::warning("Không tìm thấy token cho shop: {$shop->shop_name}");
                    continue;
                }

                $client = $this->tiktok->client();
                $client->setAccessToken($token);
                $client->setShopCipher($shop->shop_cipher);

                $apiStatements = $this->tiktok->getStatementsUpToNow($client);

                if (empty($apiStatements)) {
                    continue;
                }

                foreach ($apiStatements as $item) {
                    $statementDate = isset($item['statement_time']) ? Carbon::createFromTimestamp($item['statement_time']) : null;

                    TiktokStatement::updateOrCreate(
                        [
                            'statement_id' => $item['id'] ?? null
                        ],
                        [
                            'shop_code' => $shop->shop_code,
                            'statement_date' => $statementDate,
                            'status' => $item['payment_status'] ?? 'PROCESSING',
                            'settlement_amount' => $item['settlement_amount'] ?? 0,
                            'net_sales' => $item['net_sales_amount'] ?? 0,
                            'shipping' => $item['shipping_cost_amount'] ?? 0,
                            'fee' => $item['fee_amount'] ?? 0,
                            'adjustment' => $item['adjustment_amount'] ?? 0,
                            'payout_id' => $item['payment_id'] ?? null,
                        ]
                    );

                    $totalSyncCount++;
                }

            } catch (\Exception $e) {
                Log::error("Lỗi Sync TikTok Statements cho shop {$shop->shop_name}: " . $e->getMessage());
            }
        }

        return redirect()->back()->with('success', "Đồng bộ hoàn tất! Tổng cộng đã xử lý {$totalSyncCount} dòng dữ liệu.");
    }
}