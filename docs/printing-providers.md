# Tích hợp nhà in POD ("Send to printer")

Tài liệu cho developer đến sau: hiểu kiến trúc tích hợp nhà in (Print-on-Demand) ở
trang `/orders`, và cách **thêm một nhà in mới** mà không phải sửa controller.

Nhà in đầu tiên đã tích hợp: **FlashShip** (https://docs.flashship.net).

---

## 1. Bức tranh tổng thể

Trang `/orders` (render bởi `ShopUsController@index` → `pages/order/index.blade.php`)
hiển thị danh sách đơn `shop_us`. Mỗi đơn có dropdown **Action**:

- **Print setup** — cấu hình thông tin in (design, vị trí, printer, shipment, label…),
  lưu vào `shop_us.products` (theo từng sản phẩm) + `shop_us.print` (cấp đơn).
  Dropdown **Printer** đọc từ `config('printing.printers')` (không hard-code trong blade).
- **Send to printer** — gửi đơn đã cấu hình tới nhà in ở **chế độ nền**. Nhà in nhận đơn
  được suy ra từ printer đã chọn (`print.printer` → `printers.{printer}.provider`).

Luồng `Send to printer`:

```
Nút "Send to printer" (form POST)
  └─> POST /orders/{orderId}/send-to-printer   (ShopUsController@sendToPrinter)
        1. Tìm ShopUs theo order_id
        2. authorizeOrderAccess()      → chống IDOR (user thường chỉ gửi đơn shop mình)
        3. Bắt buộc đã có Print setup   → empty($order->print) thì chặn
        4. Chống double-submit          → print_status == 'sending' thì chặn
        5. set print_status = 'sending'
        6. providerKey = print_provider (đã set lúc Save) ?: map lại từ print.printer
           (null → factory dùng printing.default)
        7. SendOrderToPrinterJob::dispatchAfterResponse($order->id, $providerKey)
  └─> redirect back (response trả ngay, không treo trình duyệt)

SendOrderToPrinterJob (chạy SAU response)
  └─> PrintProviderFactory::make($providerKey)     → resolve provider theo config
  └─> provider->createOrder(PrintOrderRequest::fromShopUs($order))
  └─> cập nhật print_status = sent | pending_payment | failed
        (+ print_provider, provider_order_id khi thành công)
```

> Vì `QUEUE_CONNECTION=sync`, ta dùng `dispatchAfterResponse()` (chạy nền sau khi
> response đã gửi cho browser) — giống flow "Create Label" có sẵn. Không cần queue worker.

---

## 2. Sơ đồ file

```
config/printing.php                                  # default provider + 'providers' (cấu hình từng nhà in) + 'printers' (dropdown UI, map printer->provider)
app/Services/Printing/
├── Contracts/PrintProvider.php                      # interface chung (hợp đồng)
├── DTO/
│   ├── PrintOrderRequest.php                        # dữ liệu đơn đã chuẩn hoá + fromShopUs()
│   ├── PrintOrderItem.php                            # 1 dòng sản phẩm đã chuẩn hoá
│   └── PrintOrderResult.php                          # kết quả đã chuẩn hoá
├── Providers/FlashShipProvider.php                  # adapter riêng FlashShip (phần duy nhất vendor-specific)
├── PrintProviderFactory.php                         # make('flashship') -> provider
└── Exceptions/PrintException.php
app/Jobs/SendOrderToPrinterJob.php                   # gửi nền + cập nhật trạng thái
app/Http/Controllers/ShopUsController.php            # sendToPrinter(), savePrintInfo(), authorizeOrderAccess()
routes/web.php                                       # orders.send-to-printer, orders.save-print-info
resources/views/pages/order/index.blade.php          # nút + badge trạng thái + modal Print setup
database/migrations/..._add_print_status_to_shop_us.php
```

Nguyên tắc tách lớp:
- **DTO** mang dữ liệu **trung lập** (không biết gì về FlashShip).
- **Provider** dịch DTO sang định dạng dây của nhà in (endpoint, tên field, mã số…).
- **Controller/Job** chỉ làm việc qua `PrintProvider` interface → không phụ thuộc nhà in cụ thể.

---

## 3. Mô hình dữ liệu (`shop_us`)

| Cột | Kiểu | Ý nghĩa |
|---|---|---|
| `products` | json | Mảng sản phẩm. Print setup gộp thêm: `variant_id, product_type, color, size, design_url, mockup_url, print_position[], special_print, is_embroidered, note` (giữ nguyên key gốc `sku, quantity, price, product_name, product_image…`). |
| `print` | json | Cấp đơn: `{ shipment, printer, shipping_label_url }`. **Có giá trị = đã làm Print setup.** `printer` = **key trong `config.printers`** (ý định người dùng chọn, vd `flashship`). |
| `print_provider` | string | Provider key (vd `flashship`) sẽ nhận đơn. Được set **ngay khi bấm Save** ở Print setup (map từ printer đã chọn qua `providerKeyForPrinter()`); Job xác nhận lại khi gửi thành công. `null` = printer chưa map provider → dùng `printing.default`. |
| `provider_order_id` | string | Mã đơn nhà in trả về (FlashShip: `order_code`). |
| `print_status` | string | Vòng đời trạng thái (xem dưới). |

### Vòng đời `print_status`

```
not_sent ──(bấm Send)──> sending ──┬──> sent              (FlashShip nhận đơn, code FLS_200)
                                    ├──> pending_payment   (FLS_406: tạo được nhưng nợ số dư)
                                    └──> failed            (lỗi/exception; cho phép gửi lại)
```

Hằng số khai báo ở `App\Models\ShopUs::PRINT_*`. Badge hiển thị ở cột Action của blade.

---

## 4. Thêm một nhà in mới (vd "PrintBee")

**Không sửa controller/job/blade** — chỉ chạm Provider class + `config/printing.php`:

### Bước 1 — Viết Provider implements `PrintProvider`

`app/Services/Printing/Providers/PrintBeeProvider.php`:

```php
namespace App\Services\Printing\Providers;

use App\Services\Printing\Contracts\PrintProvider;
use App\Services\Printing\DTO\PrintOrderRequest;
use App\Services\Printing\DTO\PrintOrderResult;
use Illuminate\Support\Facades\Http;

class PrintBeeProvider implements PrintProvider
{
    public function __construct(private array $config) {}

    public function key(): string { return 'printbee'; }

    public function createOrder(PrintOrderRequest $request): PrintOrderResult
    {
        $payload = $this->mapPayload($request);      // map DTO -> định dạng PrintBee
        $res = Http::baseUrl($this->config['base_url'])
            ->withToken($this->accessToken())
            ->post('/orders', $payload)->json();

        return new PrintOrderResult(
            success: ($res['ok'] ?? false) === true,
            providerOrderId: $res['id'] ?? null,
            code: $res['code'] ?? null,
            message: $res['message'] ?? '',
            raw: $res,
        );
    }

    public function getOrderStatus(string $providerOrderId): ?string { /* ... */ }

    // private mapPayload(), accessToken()…  ← toàn bộ thứ riêng PrintBee nằm gọn ở đây
}
```

### Bước 2 — Khai báo trong `config/printing.php`

```php
'providers' => [
    'flashship' => [ /* ... */ ],
    'printbee' => [
        'driver'   => \App\Services\Printing\Providers\PrintBeeProvider::class,
        'base_url' => env('PRINTBEE_BASE_URL'),
        'api_token'=> env('PRINTBEE_API_TOKEN'),
        // ... config riêng provider
    ],
],
```

### Bước 3 — Cho phép chọn ở dropdown Printer

Trong `config('printing.printers')`, mở khoá entry tương ứng và trỏ về provider vừa thêm:

```php
'printers' => [
    // ...
    'printbee' => ['label' => 'PrintBee', 'enabled' => true, 'provider' => 'printbee'],
    // (label hiển thị và key printer độc lập với key provider — map qua trường 'provider')
],
```

- `enabled => true` → hết bị disabled trong dropdown, seller chọn được.
- `provider => 'printbee'` → khi Send to printer, `sendToPrinter()` lấy key này truyền vào Job.

Ngoài ra vẫn có thể ép provider mặc định toàn hệ thống bằng `PRINT_PROVIDER=printbee` trong `.env`
(áp dụng cho các đơn không map được printer → provider).

Xong. `PrintOrderRequest::fromShopUs()` đã chuẩn hoá dữ liệu chung; provider mới tự lo phần map.

> Nếu nhà in mới cần dữ liệu mà DTO chưa có → bổ sung field vào `PrintOrderRequest`/`PrintOrderItem`
> và map thêm trong `fromShopUs()`. Đây là chỗ duy nhất chạm tới khi mở rộng dữ liệu.

---

## 5. FlashShip — chi tiết adapter

`FlashShipProvider` (POD API v2). Tham chiếu: https://docs.flashship.net

### Cấu hình `.env`
```
PRINT_PROVIDER=flashship
FLASHSHIP_BASE_URL=https://uat-api.flashship.net/seller-api-v2   # test (UAT)
# FLASHSHIP_BASE_URL=https://api.flashship.net/seller-api-v2     # production (cần whitelist IP)
FLASHSHIP_API_TOKEN=        # ưu tiên: token tĩnh hạn 1 năm (không cần gọi /token)
FLASHSHIP_USERNAME=         # fallback nếu không có token
FLASHSHIP_PASSWORD=
FLASHSHIP_PRINT_TYPE=1      # 1=DTF, 2=DTG, 3=Basic DTF
```

> **UAT vs Production**: UAT để test (không in/ship thật, không trừ tiền, không cần whitelist IP).
> Production tạo đơn THẬT, trừ số dư thật, **bắt buộc khai báo IP server cho FlashShip**.
> Credentials & `variant_id` ở 2 môi trường **tách biệt**, không dùng chéo.

### Endpoint dùng tới
| Việc | Method | Path |
|---|---|---|
| Login | POST | `/token` → `data.access_token` (Bearer). Hoặc dùng API token tĩnh. |
| Tạo đơn áo | POST | `/orders/shirt-add` → `data` = `order_code` |
| Trạng thái đơn | GET | `/orders/{order_code}` → `status` |
| Danh sách variant | GET | `/orders/list-variant-sku` (lấy `variant_id`) |

Envelope phản hồi: `{ "code": "FLS_200", "msg": "...", "err": null, "data": ... }`.
Mã: `FLS_200` thành công · `FLS_406` tạo được nhưng nợ tiền (PENDING) · khác = lỗi.
(Một số lỗi auth trả `{msg:"fail", err:"...", data:null}` không có `code`.)

### Bảng map dữ liệu (ShopUs → shirt-add)
| FlashShip | Nguồn trong dự án |
|---|---|
| `buyer_first_name` / `buyer_last_name` | tách từ `shop_us.customer_name` |
| `buyer_address1`, `buyer_city`, `buyer_zip` | `customer_address`, `customer_city`, `customer_postcode` |
| `buyer_province_code` | `customer_state` → mã 2 ký tự (map tên bang US trong provider) |
| `buyer_country_code` | `customer_country` → `US` (mặc định) |
| `shipment` (số) | `print.shipment` qua `shipment_map` (Standard→1, Rush Product→3, Expedite Tiktok→6) |
| `link_label` | `print.shipping_label_url` |
| `products[].variant_id` | `products[].variant_id` (**bắt buộc**, nhập ở Print setup) |
| `products[].printer_design_{pos}_url` | `products[].design_url` áp cho mỗi vị trí trong `print_position[]` |
| `products[].mockup_{pos}_url` | `products[].mockup_url` |
| `products[].quantity`, `note`, `special_print`, `printType` | tương ứng (printType mặc định từ config) |

Vị trí hỗ trợ: `front, back, neck, right, left, pocket, hood, neck_inner`
(các nhãn khác như "3D", "Center Front" sẽ bị bỏ qua khi map).

---

## 6. Giới hạn hiện tại / TODO

- [ ] **Chưa test gửi LIVE**: cần `FLASHSHIP_API_TOKEN` hoặc user/pass thật (UAT). Hiện chưa có
      thì job set `print_status = failed` một cách an toàn.
- [ ] **Một `design_url` cho mọi vị trí in**: model Print setup hiện chỉ có 1 design/product nên
      khi chọn Front+Back sẽ dùng CHUNG một design. Muốn design khác nhau theo từng mặt → mở rộng
      Print setup thành design-per-position (đổi `products[].design_url` thành map theo vị trí, và
      cập nhật `FlashShipProvider::buildProduct()`).
- [ ] **`variant_id` nhập tay** ở Print setup. Có thể làm dropdown từ `GET /orders/list-variant-sku`
      hoặc bảng map `sku → variant_id` để tự suy ra.
- [ ] **Webhook FlashShip** (order:status:updated, order:shipment:created…) chưa nối. Khi nối, cập nhật
      `print_status`/`tracking_number` tự động thay vì phải gọi `getOrderStatus`. Có `x-signature`
      (HMAC SHA-256 với webhook secret) cần verify.
- [ ] **Cancel order**: FlashShip có `POST /orders/seller-reject`; có thể thêm method `cancelOrder()`
      vào interface nếu cần.
- [ ] **Ornament order**: hiện chỉ map `shirt-add`. FlashShip còn `/orders/ornament-add` (payload đơn giản hơn).

---

## 7. Kiểm thử nhanh (không cần credentials)

```php
// Dựng payload từ một ShopUs giả lập để xem map có đúng không (không gọi mạng):
$req = \App\Services\Printing\DTO\PrintOrderRequest::fromShopUs($order);
$provider = \App\Services\Printing\PrintProviderFactory::make();   // FlashShipProvider
$m = new ReflectionMethod($provider, 'buildShirtPayload'); $m->setAccessible(true);
dd($m->invoke($provider, $req));
```

Test guard qua HTTP: gửi khi `print` rỗng → bị chặn (status giữ `not_sent`); gửi khi
`print_status='sending'` → bị chặn (giữ `sending`).
