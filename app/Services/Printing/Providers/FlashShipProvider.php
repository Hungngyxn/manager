<?php

namespace App\Services\Printing\Providers;

use App\Services\Printing\Contracts\PrintProvider;
use App\Services\Printing\DTO\PrintOrderItem;
use App\Services\Printing\DTO\PrintOrderRequest;
use App\Services\Printing\DTO\PrintOrderResult;
use App\Services\Printing\Exceptions\PrintException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Adapter cho FlashShip POD API v2 (https://docs.flashship.net).
 * Đây là phần DUY NHẤT phụ thuộc FlashShip; mọi thứ khác đi qua PrintProvider.
 */
class FlashShipProvider implements PrintProvider
{
    /** Các vị trí in FlashShip hỗ trợ: nhãn Print setup (lowercase) -> stem field. */
    private const POSITION_MAP = [
        'front' => 'front',
        'back' => 'back',
        'neck' => 'neck',
        'right' => 'right',
        'left' => 'left',
        'pocket' => 'pocket',
        'hood' => 'hood',
        'neck inner' => 'neck_inner',
        'neck_inner' => 'neck_inner',
        'neck label inner' => 'neck_inner',
    ];

    public function __construct(private array $config)
    {
    }

    public function key(): string
    {
        return 'flashship';
    }

    public function createOrder(PrintOrderRequest $request): PrintOrderResult
    {
        $payload = $this->buildShirtPayload($request);

        $response = $this->http()->post('/orders/shirt-add', $payload);
        $json = $response->json() ?? [];
        Log::info($response);

        $code = $json['code'] ?? null;
        $isSuccess = $this->codeIs($code, '200');
        $isPending = $this->codeIs($code, '406'); // tạo được nhưng thiếu số dư -> PENDING

        return new PrintOrderResult(
            success: $isSuccess || $isPending,
            providerOrderId: is_string($json['data'] ?? null) ? $json['data'] : null,
            code: $code,
            message: (string) ($json['msg'] ?? ($json['err'] ?? 'Unknown response')),
            pendingPayment: $isPending,
            raw: $json,
        );
    }

    public function getOrderStatus(string $providerOrderId): ?string
    {
        $response = $this->http()->get('/orders/' . $providerOrderId);

        return $response->json('status');
    }

    /* ───────────────────────── Internal ───────────────────────── */

    private function http(): PendingRequest
    {
        return Http::baseUrl(rtrim($this->config['base_url'] ?? '', '/'))
            ->timeout(60)
            ->acceptJson()
            ->withToken($this->accessToken());
    }

    /**
     * Lấy access token: ưu tiên API token tĩnh; nếu không có thì đăng nhập và cache.
     */
    private function accessToken(): string
    {
        if (!empty($this->config['api_token'])) {
            return $this->config['api_token'];
        }

        return Cache::remember('flashship.access_token', 4 * 3600, function () {
            $baseUrl = rtrim(trim($this->config['base_url'] ?? 'https://uat-api.flashship.net/seller-api-v2'), '/');
            $response = Http::baseUrl($baseUrl)
                ->timeout(30)
                ->acceptJson()
                ->post('/token', [
                    'username' => 'testuser' ?? '',
                    'password' => 'testpassword' ?? '',
                ]);
            Log::info($response);
            $token = $response->json('data.access_token');
            if (!$token) {
                throw new PrintException(
                    'Đăng nhập FlashShip thất bại: ' . (string) ($response->json('msg') ?? $response->status())
                );
            }

            return $token;
        });
    }

    /**
     * @return array<string,mixed>
     */
    private function buildShirtPayload(PrintOrderRequest $r): array
    {
        if (empty($r->items)) {
            throw new PrintException('Đơn không có sản phẩm nào để in.');
        }

        return [
            'order_id' => $r->externalOrderId,
            'buyer_first_name' => $r->buyerFirstName,
            'buyer_last_name' => $r->buyerLastName,
            'buyer_email' => $r->buyerEmail,
            'buyer_phone' => $r->buyerPhone,
            'buyer_address1' => $r->buyerAddress1,
            'buyer_address2' => $r->buyerAddress2 ?? '',
            'buyer_city' => $r->buyerCity,
            'buyer_province_code' => $this->provinceCode($r->buyerProvince),
            'buyer_zip' => $r->buyerZip,
            'buyer_country_code' => $this->countryCode($r->buyerCountry),
            'shipment' => $this->shipmentCode($r->shipment),
            'link_label' => $r->labelUrl,
            'products' => array_map(fn(PrintOrderItem $it) => $this->buildProduct($it), $r->items),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function buildProduct(PrintOrderItem $item): array
    {
        if (!$item->variantId) {
            throw new PrintException('Thiếu variant_id cho sản phẩm (FlashShip bắt buộc). Hãy điền ở Print setup.');
        }

        $product = [
            'variant_id' => $item->variantId,
            'quantity' => max($item->quantity, 1),
            'note' => $item->note,
            'printType' => $item->printType ?? (int) ($this->config['default_print_type'] ?? 1),
        ];

        if ($item->specialPrint) {
            $product['special_print'] = 1;
        }

        // Một design dùng chung cho các vị trí đã chọn (mô hình Print setup hiện tại).
        $stems = $this->mapPositions($item->positions) ?: ['front'];

        $hasDesign = false;
        foreach ($stems as $stem) {
            if ($item->designUrl) {
                $product["printer_design_{$stem}_url"] = $item->designUrl;
                $hasDesign = true;
            }
            if ($item->mockupUrl) {
                $product["mockup_{$stem}_url"] = $item->mockupUrl;
            }
        }

        if (!$hasDesign) {
            throw new PrintException('Thiếu Design URL cho sản phẩm (cần ít nhất một vị trí in).');
        }

        return $product;
    }

    /**
     * Nhãn vị trí thô -> stem FlashShip, loại trùng và bỏ vị trí không hỗ trợ.
     *
     * @param string[] $positions
     * @return string[]
     */
    private function mapPositions(array $positions): array
    {
        $stems = [];
        foreach ($positions as $pos) {
            $key = strtolower(trim((string) $pos));
            if (isset(self::POSITION_MAP[$key])) {
                $stems[self::POSITION_MAP[$key]] = true;
            }
        }

        return array_keys($stems);
    }

    private function shipmentCode(?string $shipment): int
    {
        $map = $this->config['shipment_map'] ?? [];

        return (int) ($map[$shipment] ?? 1); // mặc định FirstClass
    }

    private function countryCode(?string $country): string
    {
        $country = trim((string) $country);
        if ($country === '') {
            return 'US';
        }
        if (strlen($country) === 2) {
            return strtoupper($country);
        }

        return stripos($country, 'united states') !== false ? 'US' : strtoupper(substr($country, 0, 2));
    }

    /**
     * Chuẩn hoá bang về mã 2 ký tự (FlashShip yêu cầu viết tắt bang US).
     */
    private function provinceCode(?string $province): string
    {
        $province = trim((string) $province);
        if ($province === '') {
            return '';
        }
        if (strlen($province) === 2) {
            return strtoupper($province);
        }

        return self::US_STATES[ucwords(strtolower($province))] ?? strtoupper(substr($province, 0, 2));
    }

    /** Tên bang US -> mã 2 ký tự. */
    private const US_STATES = [
        'Alabama' => 'AL',
        'Alaska' => 'AK',
        'Arizona' => 'AZ',
        'Arkansas' => 'AR',
        'California' => 'CA',
        'Colorado' => 'CO',
        'Connecticut' => 'CT',
        'Delaware' => 'DE',
        'Florida' => 'FL',
        'Georgia' => 'GA',
        'Hawaii' => 'HI',
        'Idaho' => 'ID',
        'Illinois' => 'IL',
        'Indiana' => 'IN',
        'Iowa' => 'IA',
        'Kansas' => 'KS',
        'Kentucky' => 'KY',
        'Louisiana' => 'LA',
        'Maine' => 'ME',
        'Maryland' => 'MD',
        'Massachusetts' => 'MA',
        'Michigan' => 'MI',
        'Minnesota' => 'MN',
        'Mississippi' => 'MS',
        'Missouri' => 'MO',
        'Montana' => 'MT',
        'Nebraska' => 'NE',
        'Nevada' => 'NV',
        'New Hampshire' => 'NH',
        'New Jersey' => 'NJ',
        'New Mexico' => 'NM',
        'New York' => 'NY',
        'North Carolina' => 'NC',
        'North Dakota' => 'ND',
        'Ohio' => 'OH',
        'Oklahoma' => 'OK',
        'Oregon' => 'OR',
        'Pennsylvania' => 'PA',
        'Rhode Island' => 'RI',
        'South Carolina' => 'SC',
        'South Dakota' => 'SD',
        'Tennessee' => 'TN',
        'Texas' => 'TX',
        'Utah' => 'UT',
        'Vermont' => 'VT',
        'Virginia' => 'VA',
        'Washington' => 'WA',
        'West Virginia' => 'WV',
        'Wisconsin' => 'WI',
        'Wyoming' => 'WY',
        'District Of Columbia' => 'DC',
    ];

    private function codeIs(?string $code, string $num): bool
    {
        return in_array($code, ["FLS_{$num}", "FLS-{$num}"], true);
    }
}
