<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\SellerHasShop;
use App\Models\TiktokPayout; // Giả định tên Model Payout của bạn
use App\Services\TikTokService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TiktokPayoutController extends Controller
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
            $shopCodes = SellerHasShop::pluck('shop_code')
                ->filter()
                ->unique()
                ->toArray();

            $query = TiktokPayout::query();
        } else {
            $shopCodes = SellerHasShop::where('team_id', 10)
                ->where('user_id', $user->id)
                ->pluck('shop_code')
                ->filter()
                ->unique()
                ->toArray();

            $query = TiktokPayout::whereIn('shop_code', $shopCodes);
        }

        // 2. Bộ lọc tìm kiếm dữ liệu (Filters)
        if ($request->filled('shop_code')) {
            $query->where('shop_code', $request->shop_code);
        }

        if ($request->filled('bank_account')) {
            $query->where('bank_account', 'like', '%' . $request->bank_account . '%');
        }

        if ($request->filled('payout_id')) {
            $query->where('payout_id', 'like', '%' . $request->payout_id . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('payout_initiation_date', '>=', Carbon::parse($request->from_date));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('payout_initiation_date', '<=', Carbon::parse($request->to_date));
        }

        // 3. Logic Sort dữ liệu động qua click mũi tên ở tiêu đề cột
        $sortField = $request->get('sort', 'payout_initiation_date');
        $sortDirection = strtolower($request->get('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        $allowedSortFields = [
            'payout_initiation_date',
            'payout_id',
            'payout_amount',
            'settlement_amount',
            'amount_before_exchange',
            'reserve_amount',
            'payout_completion_date',
            'status'
        ];

        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'payout_initiation_date';
        }

        $payouts = $query->orderBy($sortField, $sortDirection)
            ->paginate(20)
            ->withQueryString();

        return view('pages.finance.payout', compact('payouts', 'shopCodes'));
    }

    public function syncPayouts(Request $request)
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

                $apiPayouts = $this->tiktok->getPayoutsUpToNow($client);

                if (empty($apiPayouts)) {
                    continue;
                }

                foreach ($apiPayouts as $item) {
                    $initiationDate = isset($item['create_time']) ? Carbon::createFromTimestamp($item['create_time']) : null;
                    $completionDate = isset($item['paid_time']) ? Carbon::createFromTimestamp($item['paid_time']) : null;

                    TiktokPayout::updateOrCreate(
                        [
                            'payout_id' => $item['id'] ?? null
                        ],
                        [
                            'shop_code' => $shop->shop_code,
                            'payout_amount' => $item['amount']['value'] ?? 0,
                            'settlement_amount' => $item['settlement_amount']['value'] ?? 0,
                            'amount_before_exchange' => $item['payment_amount_before_exchange']['value'] ?? 0,
                            'reserve_amount' => $item['reserve_amount']['value'] ?? 0,
                            'payout_initiation_date' => $initiationDate,
                            'payout_completion_date' => $completionDate,
                            'status' => $item['status'] ?? 'Processing',
                            'bank_account' => $item['bank_account'] ?? null,
                        ]
                    );

                    $totalSyncCount++;
                }

            } catch (\Exception $e) {
                Log::error("Lỗi Sync TikTok Payouts cho shop {$shop->shop_name}: " . $e->getMessage());
            }
        }

        return redirect()->back()->with('success', "Đồng bộ hoàn tất! Tổng cộng đã xử lý {$totalSyncCount} dòng dữ liệu Payout.");
    }
}