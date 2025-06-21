<?php

namespace App\Services;

use App\Models\Sku;
use App\Models\TiktokToken;
use Carbon\Carbon;
use EcomPHP\TiktokShop\Client;
use App\Models\Order;
use App\Models\SellerHasShop;
use Illuminate\Support\Facades\Auth;

class TikTokService
{
    public function client(): Client
    {
        return new Client(
            config('tiktokshop.app_key'),
            config('tiktokshop.secret_key')
        );
    }

    public function authorizeUrl(): string
    {
        return 'https://developer.sellersprint.com/open/authorize?key=0J8CJNYBtfLdRBjickYSGA%3D%3D';
    }

    public function getAccessToken(int $userId): string
    {
        $token = TiktokToken::where('user_id', $userId)->first();

        if (!$token) {
            throw new \Exception("Không tìm thấy token cho user $userId");
        }

        if (now()->gte($token->expires_at)) {
            // Token hết hạn → gọi refresh API
            $client = $this->client();
            $newToken = $client->auth()->refreshNewToken($token->refresh_token);

            $token->update([
                'access_token' => $newToken['access_token'],
                'refresh_token' => $newToken['refresh_token'] ?? $token->refresh_token,
                'expires_at' => now()->addSeconds($newToken['expires_in']),
            ]);

            return $newToken['access_token'];
        }

        return $token->access_token;
    }

    public function fetchAccessToken(Client $client, string $code): string
    {
        $token = $client->auth()->getToken($code);
        $access_token = $token['access_token'] ?? null;
        $refresh_token = $token['refresh_token'] ?? null;
        $expires_in = Carbon::createFromTimestamp($token['access_token_expire_in']) ?? null;
        $shop_name = $token['seller_name'] ?? null;

        if (!$access_token || !$expires_in) {
            throw new \Exception('Không lấy được access token hoặc thời gian hết hạn');
        }

        TiktokToken::updateOrCreate(
            ['shop_name' => $shop_name],
            [
                'user_id' => auth()->id(),
                'access_token' => $access_token,
                'refresh_token' => $refresh_token,
                'expires_at' => $expires_in,
            ]
        );

        return $access_token;
    }


    public function fetchShopCipher(Client $client): string
    {
        $shops = $client->Authorization->getAuthorizedShop();

        $body = [
            'page_size' => 50,
            'page' => 1,
            'statement_time_ge' => now()->subMonths(1)->startOfYear()->timestamp,
            'statement_time_le' => now()->timestamp,
            'shop_cipher' => $shops['shops'][0]['cipher']
        ];

        $response = $client->Finance->getStatements($body);

        return $shops['shops'][0]['cipher'] ?? throw new \Exception('Không lấy được shop_cipher');
    }

    public function fetchOrderList(Client $client, int $pageSize = 100): array
    {
        $allOrders = [];
        $pageToken = null;

        do {
            $params = [
                'page_size' => $pageSize,
            ];

            if ($pageToken) {
                $params['page_token'] = $pageToken;
            }

            $response = $client->Order->getOrderList($params);
            $orders = $response['orders'] ?? [];
            $pageToken = $response['next_page_token'] ?? null;

            $allOrders = array_merge($allOrders, $orders);

        } while (!empty($pageToken));
        return $allOrders;
    }


    public function syncOrders(array $orders, string $shopCipher): void
    {
        $shop = SellerHasShop::where('shop_cipher', $shopCipher)->first();
        foreach ($orders as $order) {
            $lineItems = $order['line_items'] ?? [];
            $createdAt = Carbon::createFromTimestamp($order['create_time']);

            foreach ($lineItems as $item) {
                $skuCode = $item['seller_sku'] ?? null;
                $quantity = $item['quantity'] ?? 1;
                $isCanceled = isset($order['cancellation_initiator']);

                $sku = Sku::where('sku', $skuCode)->first();
                if (!$sku) {
                    $sku = null;
                    $orderItem = Order::firstOrNew(['order_id' => $item['id']]);

                    $orderItem->fill([
                        'extra_id' => $order['id'],
                        'sku' => $skuCode,
                        'shop_name' => $shop?->shop_name,
                        'user_id' => $shop?->user_id,
                        'shop_cipher' => $shopCipher,
                        'quantity' => $quantity,
                        'create_at' => $createdAt,
                        'status' => $item['display_status']
                    ]);

                    $orderItem->created_at = $createdAt;
                    $orderItem->save();
                    continue;
                }

                // $displayStatus = strtoupper( ?? '');

                $total = isset($item['sale_price']) ? $item['sale_price'] * $quantity : 0;
                $status = $item['display_status'];

                // Tính toán cost, profit, bonus
                $orderService = new OrderService($sku, $quantity, $total);
                $calculated = $orderService->calculate();

                if ($isCanceled) {
                    $total = 0;
                    $calculated['cost'] = 0;
                    $calculated['profit'] = 0;
                    $calculated['bonus'] = 0;
                    $status = 'CANCELLED/REFUND';
                }

                $orderItem = Order::firstOrNew(['order_id' => $item['id']]);

                $orderItem->fill([
                    'extra_id' => $order['id'],
                    'sku' => $skuCode,
                    'shop_name' => $shop?->shop_name,
                    'user_id' => $shop?->user_id,
                    'shop_cipher' => $shopCipher,
                    'quantity' => $quantity,
                    'cost' => $calculated['cost'],
                    'total' => $total,
                    'profit' => $calculated['profit'],
                    'bonus' => $calculated['bonus'],
                    'create_at' => $createdAt,
                    'status' => $status
                ]);

                $orderItem->created_at = $createdAt;
                $orderItem->save();
            }
        }
    }

    public function saveOrUpdateShop(string $accessToken, string $shopCipher): void
    {

        SellerHasShop::updateOrCreate(
            ['shop_code' => session('shop_code')],
            [
                'shop_name' => session('shop_name'),
                'shop_code' => session('shop_code'),
                'user_id' => Auth::id(),
                'access_token' => $accessToken,
                'shop_cipher' => $shopCipher,
            ]
        );
    }
}
