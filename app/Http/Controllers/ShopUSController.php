<?php

namespace App\Http\Controllers;

use App\Models\SellerHasShop;
use App\Models\ShopUs;
use App\Models\TiktokToken;
use App\Services\TikTokService;
use Illuminate\Http\Request;
use Log;

class ShopUsController extends Controller
{
    protected $tiktok;

    public function __construct(TikTokService $tiktok)
    {
        $this->tiktok = $tiktok;
    }
    /**
     * Hiển thị danh sách đơn hàng ShopUS
     */
    public function index()
    {
        $orders = ShopUs::orderByDesc('created_at')->get();

        return view('pages.shopus', compact('orders'));
    }

    /**
     * Cập nhật Tracking Number và Label Link
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:shop_us,id',
            'tracking_number' => 'nullable|string|max:255',
            'label_link' => 'nullable|string|max:500',
        ]);

        $order = ShopUs::findOrFail($validated['id']);
        $order->update([
            'tracking_number' => $validated['tracking_number'] ?? null,
            'label_link' => $validated['label_link'] ?? null,
        ]);

        return redirect()->back()->with('success', 'ShopUS order updated successfully.');
    }

    public function createLabel()
    {
        try {
            $shops = SellerHasShop::where('team_id', 10)->get();

            foreach ($shops as $shop) {

                $token = TiktokToken::where('shop_name', $shop->shop_name)->first();

                if (!$token) {
                    Log::warning("Không tìm thấy token cho shop: {$shop->shop_name}");
                    continue;
                }

                $client = $this->tiktok->client();
                $client->setAccessToken($token->access_token);
                $client->setShopCipher($shop->shop_cipher);

                $orders = $this->tiktok->fetchOrderList($client);

                if (empty($orders)) {
                    Log::info("Shop {$shop->shop_name} không có đơn hàng nào.");
                    continue;
                }

                foreach ($orders as $order) {
                    $orderId = $order['id'];
                    $package = $client->Fulfillment->createPackages($orderId);
                    $label = $client->Fulfillment->getPackageShippingDocument($package['package_id'], 'SHIPPING_LABEL', 'A6');

                    Log::info("Shop {$shop->shop_name} - Xử lý Order ID: {$orderId}");
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Đã xử lý xong tất cả đơn hàng.'
            ]);

        } catch (\Throwable $e) {
            Log::error('Lỗi financeShop: ' . $e->getMessage());
            return response()->json(['error' => 'Lỗi truy xuất báo cáo tài chính'], 500);
        }
    }

}
