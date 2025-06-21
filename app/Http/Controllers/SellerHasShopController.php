<?php

namespace App\Http\Controllers;

use App\Imports\ShopImport;
use App\Models\Order;
use App\Models\SellerHasShop;
use App\Models\User;
use App\Services\TikTokService;
use EcomPHP\TiktokShop\Client;
use Http;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Log;
use Maatwebsite\Excel\Facades\Excel;

class SellerHasShopController extends Controller
{
    protected $tiktok;

    public function __construct(TikTokService $tiktok)
    {
        $this->middleware('auth');
        $this->tiktok = $tiktok;
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $query = SellerHasShop::with('seller');

        // Nếu là User, chỉ xem Shop của mình
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

        // Nếu có search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('shop_name', 'like', '%' . $search . '%')
                    ->orWhere('shop_code', 'like', '%' . $search . '%');
            });
        }

        $totalShops = $query->count();
        $shops = $query->paginate(10)->appends($request->only(['search', 'user_id']));
        $sellers = [];

        if ($user->role->name !== 'Seller') {
            $sellers = User::whereHas('role')->get();
        }


        return view('pages.shop.index', compact('shops', 'sellers', 'totalShops'));
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
            ]);

            Order::where('shop_name', 'like', $request->shop_name . '%')
                ->update(['shop_name' => $shop->shop_name, 'user_id' => $shop->user_id]);

            DB::commit();

            return redirect()->route('shop.index')->with('success', 'Shop created successfully.');
        } catch (QueryException $e) {
            DB::rollBack();

            return redirect()->route('shop.index')->with('error', 'Failed to create shop. Maybe duplicated shop name or code.');
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
            'user_id' => 'required|exists:users,id',
            'on_hold' => 'nullable|numeric',
            'payout' => 'nullable|numeric',
        ]);

        DB::beginTransaction();
        try {
            $originalShopName = $shop->shop_name;

            $shop->update($validated);

            if ($originalShopName !== $validated['shop_name']) {
                if (str_ends_with($originalShopName, ' - Chưa được add')) {
                    Order::where('shop_name', $originalShopName)
                        ->update([
                            'shop_name' => $shop->shop_name,
                            'user_id' => $shop->user_id,
                        ]);
                }
            }

            DB::commit();

            return redirect()->route('shop.index')->with('success', 'Shop updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->route('shop.index')->with('error', 'Error updating shop: ' . $e->getMessage());
        }
    }



    public function destroy(SellerHasShop $shop)
    {
        $this->authorizeShopAccess($shop);

        DB::beginTransaction();

        try {
            Order::where('shop_name', $shop->shop_name)
                ->update(['shop_name' => $shop->shop_name . ' - Chưa được add']);

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

        if ($user->role->name === 'Seller' && $shop->seller_id !== $user->id) {
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

        $import = new ShopImport;

        try {
            \DB::beginTransaction();

            Excel::import($import, $request->file('file'));

            \DB::commit();

            return back()->with([
                'status' => 'Import shop hoàn tất!',
                'error' => 'Import thất bại dòng: ' . implode(', ', $import->skipped),
            ]);
        } catch (\Exception $e) {
            \DB::rollBack();

            return back()->with([
                'error' => 'Import thất bại: ' . $e->getMessage(),
            ]);
        }
    }


    public function downloadSample()
    {
        $path = public_path('sample_excel/sample_shop.xlsx');

        return response()->download($path, 'sample_shop.xlsx');
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

    public function tiktokCallback(Request $request)
    {
        $code = $request->input('code');
        if (!$code) {
            return redirect()->route('shop.index')->with('error', 'Không có mã code trả về từ TikTok.');
        }

        DB::beginTransaction();

        try {
            $client = $this->tiktok->client();
            $accessToken = $this->tiktok->fetchAccessToken($client, $code);
            $client->setAccessToken($accessToken);

            $shopCipher = $this->tiktok->fetchShopCipher($client);

            $client->setShopCipher($shopCipher);

            $this->tiktok->saveOrUpdateShop($accessToken, $shopCipher);

            DB::commit();

            return redirect()->route('shop.index')->with('status', 'ổn');

            // return redirect()->route('orders.sync', [
            //     'access_token' => $accessToken,
            //     'shop_cipher' => $shopCipher,
            // ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->route('shop.index')->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }

}

