<?php

namespace App\Services;

use App\Models\Sku;
use App\Models\TiktokToken;
use Carbon\Carbon;
use EcomPHP\TiktokShop\Client;
use App\Models\Order;
use App\Models\SellerHasShop;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
        $redirectUri = urlencode(config('tiktokshop.redirect_uri'));
        $state = session('tiktok_state') ?? Str::random(40);

        return "https://developer.sellersprint.com/open/authorize?key=0J8CJNYBtfLdRBjickYSGA%3D%3D";
    }

    public function getAccessToken($shop): string
    {
        $token = TiktokToken::where('shop_name', $shop->shop_name)->first();

        if (!$token) {
            throw new \Exception("Không tìm thấy token cho user $shop->shop_name");
        }
        if (1>0) {
            $client = $this->client();
            try {
                $newToken = $client->auth()->refreshNewToken($token->refresh_token);

                if (!isset($newToken['access_token'])) {
                    throw new \Exception("Không thể làm mới access_token");
                }

                $token->update([
                    'access_token' => $newToken['access_token'],
                    'refresh_token' => $newToken['refresh_token'] ?? $token->refresh_token,
                    'expires_at' => $newToken['access_token_expire_in'],
                ]);
                return $newToken['access_token'];
            } catch (\Exception $e) {
                Log::error('Refresh token failed', [
                    'user_id' => $shop->shop_name,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }

        return $token->access_token;
    }

    public function fetchAccessToken(Client $client, string $code): string
    {
        try {
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
        } catch (\Exception $e) {
            Log::error('Lỗi lấy token TikTok ban đầu', [
                'code' => $code,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function fetchShopCipher(Client $client)
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

        return $shops['shops'][0] ?? throw new \Exception('Không lấy được shop_cipher');
    }

    public function getStatements(Client $client, string $shopCipher): array
    {
        $params = [
            'page_size' => 50,
            'page' => 1,
            'shop_cipher' => $shopCipher,
            'sort_field' => 'statement_time',
            'sort_order' => 'DESC',
        ];

        try {
            $response = $client->Finance->getStatements($params);
            return $response['statements'] ?? [];
        } catch (\Exception $e) {
            Log::error('Lỗi lấy danh sách statement TikTok', [
                'params' => $params,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    // public function fetchOrderList(Client $client, int $pageSize = 100): array
    // {
    //     $allOrders = [];
    //     $pageToken = null;

    //     do {
    //         $params = [
    //             'page_size' => $pageSize,
    //         ];
    //         // //AWAITING_SHIPMENT - AWAITING_COLLECTION
    //         $body = [
    //             'order_status' => 'AWAITING_COLLECTION'
    //         ];

    //         if ($pageToken) {
    //             $params['page_token'] = $pageToken;
    //         }

    //         $response = $client->Order->getOrderList($params, $body);
    //         $orders = $response['orders'] ?? [];
    //         $pageToken = $response['next_page_token'] ?? null;

    //         $allOrders = array_merge($allOrders, $orders);

    //     } while (!empty($pageToken));

    //     return $allOrders;
    // }


    public function fetchOrderList(Client $client, int $pageSize = 100): array
    {
        $allOrders = [];
        $pageToken = null;

        do {
            $params = [
                'page_size' => $pageSize,
            ];

            $body = [
                'order_status' => 'AWAITING_SHIPMENT',
            ];

            if ($pageToken) {
                $params['page_token'] = $pageToken;
            }

            $response = $client->Order->getOrderList($params, $body);

            $orders = $response['orders'] ?? [];
            $pageToken = $response['next_page_token'] ?? null;

            $allOrders = array_merge($allOrders, $orders);
        } while (!empty($pageToken));

        return $allOrders;
    }

    public function fetchOrderDetails(Client $client, array $orderIds): array
    {
        try {
            $response = $client->Order->getOrderDetail([
                'ids' => implode(',', $orderIds)
            ]);

            return $response['order_list'] ?? $response['orders'] ?? [];
        } catch (\Exception $e) {
            Log::error('TikTokService fetchOrderDetails error', [
                'order_ids' => $orderIds,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function fetchOrderListToShip(Client $client, int $pageSize = 100): array
    {
        $allOrders = [];
        $pageToken = null;

        do {
            $params = [
                'page_size' => $pageSize,
            ];
            // //AWAITING_SHIPMENT - AWAITING_COLLECTION
            $body = [
                'order_status' => 'AWAITING_COLLECTION'
            ];

            if ($pageToken) {
                $params['page_token'] = $pageToken;
            }

            $response = $client->Order->getOrderList($params, $body);
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

                $total = isset($item['sale_price']) ? $item['sale_price'] * $quantity : 0;
                $status = $item['display_status'];

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

    public function saveOrUpdateShop(string $accessToken, array $shop): void
    {
        $shopName = $shop['name'];
        $shopCode = $shop['code'];
        $shopCipher = $shop['cipher'];

        SellerHasShop::updateOrCreate(
            ['shop_code' => $shopCode],
            [
                'shop_name' => $shopName,
                'shop_code' => $shopCode,
                'user_id' => Auth::id(),
                'access_token' => $accessToken,
                'shop_cipher' => $shopCipher,
                'team_id' => 10,
            ]
        );
    }

    public function handleOrderAndGetLabel(Client $client, string $orderId): array
    {
        dd(1);
        try {
            $packageResponse = $client->Fulfillment->createPackages($orderId);

            if (isset($packageResponse['code']) && $packageResponse['code'] !== 0) {
                return [
                    'success' => false,
                    'message' => 'Lỗi tạo gói hàng: ' . ($packageResponse['message'] ?? 'Không rõ nguyên nhân')
                ];
            }

            return [
                'success' => true,

            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Hệ thống gặp ngoại lệ: ' . $e->getMessage()
            ];
        }
    }

    public function getAllPaymentsUpToNow($client): array
    {
        $timeLt = time();

        $params = [
            'create_time_lt' => $timeLt,
            'page_size' => 100,
            'sort_field' => 'create_time',
        ];

        $response = $client->Finance->getPayments($params);

        $payments = $response['payments'] ?? [];


        return $payments;
    }

}
