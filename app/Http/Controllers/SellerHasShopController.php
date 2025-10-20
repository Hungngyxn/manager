<?php

namespace App\Http\Controllers;

use App\Imports\ShopImport;
use App\Models\Order;
use App\Models\SellerHasShop;
use App\Models\ShopAccount;
use App\Models\Team;
use App\Models\TiktokToken;
use App\Models\User;
use App\Services\TikTokService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Log;
use Maatwebsite\Excel\Facades\Excel;

class SellerHasShopController extends Controller
{
    protected $tiktok;

    public function __construct(TikTokService $tiktok)
    {
        $this->tiktok = $tiktok;
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $perPage = $request->get('perPage', 10);
        $query = SellerHasShop::with('seller')->orderBy('updated_at', 'desc');

        if ($user->role->name === 'Seller') {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('user_id') && $user->role->name !== 'Seller') {
            if ($request->user_id === 'null') {
                $query->whereNull('user_id');
            } else {
                $query->where('user_id', $request->user_id);
            }
        }

        if ($request->filled('team_id')) {
            if ($request->team_id === 'null') {
                $query->whereNull('team_id');
            } else {
                $query->where('team_id', $request->team_id);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('shop_name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('shop_code', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('filter_pending_nullbank') && $request->filter_pending_nullbank == 1) {
            $query->where(function ($q) {
                $q->where('pending', '>', 0)
                    ->orWhere('onhold', '>', 0);
            })->where('bank', '=', '-');
        }


        $totalShops = $query->count();

        $totals = [
            'pending' => (clone $query)->where('pending', '>', 0)->sum('pending'),
            'onhold' => (clone $query)->where('onhold', '>', 0)->sum('onhold'),
            'payout' => (clone $query)->where('payout', '>', 0)->sum('payout'),
        ];

        $shops = $query->paginate($perPage)->appends($request->only(['search', 'user_id']));

        $sellers = [];
        if ($user->role->name !== 'Seller') {
            $sellers = User::whereHas('role')->get();
        }

        $teams = Team::get();

        return view('pages.shop.index', compact('shops', 'sellers', 'totalShops', 'teams', 'totals'));
    }


    public function create()
    {
        $sellers = User::whereHas('role')->get();
        return view('pages.shop.create', compact('sellers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'shop_name' => 'required|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            $shop = SellerHasShop::create([
                'user_id' => $request->seller_id,
                'shop_name' => trim($request->shop_name),
                'shop_code' => $request->shop_code ?? null,
                'email' => $request->email,
            ]);

            $affectedOrders = Order::where('shop_name', 'like', $request->shop_name . '%')->get();

            foreach ($affectedOrders as $order) {
                $order->update([
                    'shop_name' => $shop->shop_name,
                    'user_id' => $shop->user_id,
                ]);
            }

            $dates = $affectedOrders
                ->pluck('created_at')
                ->map(fn($dt) => $dt->toDateString())
                ->unique();

            foreach ($dates as $date) {
                ReportController::aggregateForDate($shop->user_id, $date);
            }

            DB::commit();

            return redirect()->route('shop.index')->with('success', 'Shop created and reports updated successfully.');
        } catch (QueryException $e) {
            DB::rollBack();
            return redirect()->route('shop.index')->with('error', 'Failed to create shop. Possibly duplicated shop name or code.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('shop.index')->with('error', 'Unexpected error: ' . $e->getMessage());
        }
    }

    public function edit(SellerHasShop $shop)
    {
        $this->authorizeShopAccess($shop);
        return view('pages.shop.edit', compact('shop'));
    }

    public function update(Request $request, SellerHasShop $shop)
    {
        $this->authorizeShopAccess($shop);

        $validated = $request->validate([
            'shop_name' => 'required|string|max:255',
            'shop_code' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'user_id' => 'required|exists:users,id',
            'team_id' => 'nullable',
            'onhold' => 'nullable|numeric',
            'payout' => 'nullable|numeric',
        ]);


        try {
            $originalShopName = $shop->shop_name;
            $oldUserId = $shop->user_id;
            $shop->update($validated);

            if ($originalShopName !== $validated['shop_name']) {
                if (str_ends_with($originalShopName, ' - New Shop')) {
                    Order::where('shop_name', $originalShopName)
                        ->update([
                            'shop_name' => $shop->shop_name,
                            'user_id' => $shop->user_id,
                        ]);
                }
            }

            if (is_null($oldUserId) && $validated['user_id']) {
                Order::where('shop_name', $shop->shop_name)
                    ->update(['user_id' => $validated['user_id']]);

                $dates = Order::where('shop_name', $shop->shop_name)
                    ->pluck('created_at')
                    ->map(fn($dt) => $dt->toDateString())
                    ->unique();

                foreach ($dates as $date) {
                    ReportController::aggregateForDate($validated['user_id'], $date);
                }
            }

            if (!empty($validated['email'])) {
                ShopAccount::updateOrCreate(
                    ['email' => $validated['email']],
                    [
                        'email' => $validated['email'],
                        'user_id' => $validated['user_id'],
                    ]
                );
            }

            return redirect()->back()->with('success', 'Shop updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Error updating shop: ' . $e->getMessage());
        }
    }

    public function destroy(SellerHasShop $shop)
    {
        $this->authorizeShopAccess($shop);

        DB::beginTransaction();

        try {
            Order::where('shop_name', $shop->shop_name)
                ->update(['shop_name' => $shop->shop_name . ' - New Shop']);

            $shop->delete();

            DB::commit();

            return redirect()->route('shop.index')->with('success', 'Shop deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->route('shop.index')->with('error', 'Error deleting shop: ' . $e->getMessage());
        }
    }

    private function authorizeShopAccess(SellerHasShop $shop)
    {
        $user = auth()->user();

        if ($user->role->name === 'Seller' && $shop->user_id !== $user->id) {
            abort(403, 'Unauthorized access to shop.');
        }
    }

    public function checkSeller(Request $request)
    {
        $shopCode = $request->input('shop_code');

        $shop = SellerHasShop::where('shop_code', $shopCode)->first();

        if ($shop) {
            return response()->json([
                'success' => true,
                'shop_name' => $shop->shop_name,
                'seller_name' => optional($shop->seller)->name ?? 'Unknown'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Shop not found.'
            ]);
        }
    }

    public function importShop(Request $request)
    {
        $request->validate([
            'file.*' => 'required|mimes:xlsx,xls'
        ]);

        $import = new ShopImport();

        try {
            DB::beginTransaction();

            Excel::import($import, $request->file('file'));

            DB::commit();

            return redirect()->back()->with([
                'status' => 'Shop import completed successfully!',
                'error' => 'Failed rows: ' . implode(', ', $import->created),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with([
                'error' => 'Import failed: ' . $e->getMessage(),
            ]);
        }
    }

    public function downloadSample()
    {
        $path = public_path('sample_excel/sample_shop.xlsx');

        return response()->download($path, 'sample_shop.xlsx');
    }

    public function receiveTikTokData(Request $request)
    {
        $data = $request->all();

        if (empty($data['profile_name'])) {
            return response()->json(['error' => 'Thiếu profile_name'], 400);
        }

        $shopName = trim($data['profile_name']);
        $shopCode = $data['profile_code'] ?? null;

        if (!empty($shopCode)) {
            $shop = SellerHasShop::where('shop_code', $shopCode)->first();
        } else {
            $shop = SellerHasShop::where('shop_name', $shopName)->first();
        }

        $shopData = [
            'shop_name' => $shopName,
            'limit_order' => $data['order_limit'] ?? null,
            'shop_code' => $data['profile_code'] ?? null,
            'bank' => $data['profile_bank_code'] ?? null,
            'onhold' => str_replace(',', '', $data['profile_total_onhold'] ?? 0),
            'pending' => str_replace(',', '', $data['profile_total_pending'] ?? 0),
            'payout' => str_replace(',', '', $data['profile_total_paid'] ?? 0),
        ];

        if ($shop) {
            $shop->update($shopData);
        } else {
            $shop = SellerHasShop::create(array_merge([
                'shop_name' => $shopName,
                'user_id' => null,
            ], $shopData));
        }

        return response()->json([
            'message' => 'Cập nhật thành công',
            'shop' => $shop,
        ]);
    }

    public function connectTikTok(Request $request)
    {
        $request->validate([
            'shop_name' => 'required|string',
            'shop_code' => 'required|string',
        ]);

        session([
            'shop_name' => $request->shop_name,
            'shop_code' => $request->shop_code,
            'tiktok_state' => Str::random(40),
        ]);

        return redirect($this->tiktok->authorizeUrl());
    }

    public function reconnectTikTok($id)
    {
        $shop = SellerHasShop::findOrFail($id);

        session([
            'shop_name' => $shop->shop_name,
            'shop_code' => $shop->shop_code,
            'tiktok_state' => Str::random(40),
        ]);

        return redirect($this->tiktok->authorizeUrl());
    }

    public function financeShop()
    {
        $shop = SellerHasShop::findOrFail(2601);

        try {
            $client = $this->tiktok->client();
            $token = TiktokToken::where('shop_name', $shop->shop_name)->first();

            $client = $this->tiktok->client();
            $client->setAccessToken($token->access_token);
            $client->setShopCipher($shop->shop_cipher);

            $orders = $this->tiktok->fetchOrderList($client);
            $order_id = $orders[0]['id'];
            // $response = $client->Order->getOrderDetail($order_id);

            // $response = $client->Fulfillment->getPackageShippingDocument("1154904338926440514", 'SHIPPING_LABEL', 'A6');
            // $response = $client->Fulfillment->createPackages("577131612446625858");


            // dd($response);       


            // dd($response);

            // // Lấy access token từ DB hoặc làm mới nếu cần
            // $accessToken = $this->tiktok->getAccessToken($shop->user_id);
            // $client->setAccessToken($accessToken);

            // // Lấy shop_cipher từ DB (nếu không có thì báo lỗi)
            // $shopCipher = $shop->shop_cipher;
            // if (!$shopCipher) {
            //     return response()->json(['error' => 'Shop chưa được liên kết cipher'], 400);
            // }

            // // Lấy danh sách báo cáo theo tháng hiện tại
            // $statements = $this->tiktok->getOnHoldTransactions(
            //     $accessToken,
            //     $shopCipher,
            // );
            // dd($statements);
            // return response()->json([
            //     'shop' => $shop->shop_name,
            //     'statements' => $statements,
            // ]);

        } catch (\Throwable $e) {
            Log::error('Lỗi financeShop: ' . $e);
            return response()->json(['error' => 'Lỗi truy xuất báo cáo tài chính'], 500);
        }

        // $shop = SellerHasShop::findOrFail($id);
        // $tiktokService = new TikTokService();
        // $accessToken = $tiktokService->getAccessToken($shop->user_id);
        // $shopCipher = $shop->shop_cipher; // bạn lấy từ DB hoặc gọi hàm fetchShopCipher()

        // $onholdOrders = $tiktokService->getOnHoldTransactions($accessToken, $shopCipher);
        // dd($onholdOrders);

        // foreach ($onholdOrders as $order) {
        //     dump([
        //         'order_id' => $order['order_id'],
        //         'amount' => $order['amount'],
        //         'fee' => $order['fee'],
        //         'final_amount' => $order['final_amount'],
        //         'status' => $order['status'],
        //         'reason' => $order['withheld_reason'],
        //     ]);
        // }
    }

    public function tiktokCallback(Request $request)
    {
        $code = $request->input('code');
        if (!$code) {
            return redirect()->route('shop.index')->with('error', 'No code returned from TikTok.');
        }

        DB::beginTransaction();

        try {
            $client = $this->tiktok->client();
            $accessToken = $this->tiktok->fetchAccessToken($client, $code);
            $client->setAccessToken($accessToken);
            // dd($client);
            $shop = $this->tiktok->fetchShopCipher($client);
            $client->setShopCipher($shop['cipher']);

            $this->tiktok->saveOrUpdateShop($accessToken, $shop);

            DB::commit();

            return redirect()->route('shop.index')->with('status', 'Connected successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->route('shop.index')->with('error', 'Error: ' . $e->getMessage());
        }
    }
}
