<?php

namespace App\Jobs;

use App\Models\ShopUs;
use App\Services\Printing\DTO\PrintOrderRequest;
use App\Services\Printing\PrintProviderFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;

/**
 * Gửi một đơn ShopUS tới nhà in (FlashShip mặc định) ở chế độ nền.
 * Cập nhật shop_us.print_status: sending -> sent | pending_payment | failed.
 */
class SendOrderToPrinterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $shopUsId,
        public ?string $providerKey = null,
    ) {
    }

    public function handle(): void
    {
        $order = ShopUs::find($this->shopUsId);
        if (!$order) {
            return;
        }

        try {
            $provider = PrintProviderFactory::make($this->providerKey);
            $result = $provider->createOrder(PrintOrderRequest::fromShopUs($order));

            if ($result->success) {
                $order->update([
                    'print_provider'    => $provider->key(),
                    'provider_order_id' => $result->providerOrderId,
                    'print_status'      => $result->pendingPayment
                        ? ShopUs::PRINT_PENDING_PAYMENT
                        : ShopUs::PRINT_SENT,
                ]);

                Log::info("Send to printer OK: order {$order->order_id} via [{$provider->key()}] -> {$result->providerOrderId} ({$result->code})");
                return;
            }

            $order->update(['print_status' => ShopUs::PRINT_FAILED]);
            Log::warning('Send to printer thất bại', [
                'order_id' => $order->order_id,
                'code'     => $result->code,
                'message'  => $result->message,
                'raw'      => $result->raw,
            ]);
        } catch (\Throwable $e) {
            $order->update(['print_status' => ShopUs::PRINT_FAILED]);
            Log::error('Send to printer exception', [
                'order_id' => $order->order_id,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}
