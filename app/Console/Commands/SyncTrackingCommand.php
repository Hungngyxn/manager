<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TrackOrder;
use App\Services\SaigonApiService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SyncTrackingCommand extends Command
{
    protected $signature = 'tracking:sync';
    protected $description = 'Sync tracking orders and send delivered mail';

    protected $api;

    public function __construct(SaigonApiService $api)
    {
        parent::__construct();
        $this->api = $api;
    }

    public function handle()
    {
        $this->info('Start syncing tracking orders...');

        $synced = 0;

        TrackOrder::where(function ($q) {
            $q->where('delivered_mail_sent', false)
              ->orWhereNull('delivered_mail_sent');
        })
        ->whereNotNull('tracking_number')
        ->chunk(50, function ($orders) use (&$synced) {

            $trackingNumbers = $orders->pluck('tracking_number')->toArray();

            try {

                $data = $this->api->queryTracking($trackingNumbers);

                if (!isset($data['data']['accepted']['content'])) {
                    return;
                }

                // map tracking -> order
                $orderMap = [];
                foreach ($orders as $o) {
                    $orderMap[$o->tracking_number] = $o;
                }

                foreach ($data['data']['accepted']['content'] as $trackData) {

                    $trackNo = $trackData['trackNo'] ?? null;

                    if (!$trackNo || !isset($orderMap[$trackNo])) {
                        continue;
                    }

                    $order = $orderMap[$trackNo];

                    if (!isset($trackData['localLogisticsInfo']['trackingDetails'][0])) {
                        continue;
                    }

                    $detail = $trackData['localLogisticsInfo']['trackingDetails'][0];

                    $eventDetail = $detail['eventDetail'] ?? '';
                    $address = $detail['address'] ?? '';
                    $eventTime = $detail['eventTime'] ?? null;

                    $statusText = trim($eventDetail . ($address ? " - {$address}" : ""));

                    $order->status = $statusText;
                    $order->save();

                    // gửi mail nếu delivered lần đầu
                    if (
                        str_contains(strtolower($eventDetail), 'delivered') &&
                        !$order->delivered_mail_sent &&
                        $order->email
                    ) {

                        Mail::raw(
                            "Your order has been delivered successfully.\n\n"
                            . "Tracking Number: {$order->tracking_number}\n"
                            . "SKU: {$order->sku}\n"
                            . "Packing List:\n{$order->driver_link}\n\n",
                            function ($message) use ($order) {
                                $message->to($order->email)
                                        ->subject('Order Delivered');
                            }
                        );

                        $order->delivered_mail_sent = true;
                        $order->save();
                    }

                    $synced++;
                }

            } catch (\Throwable $e) {

                Log::error('Track123 sync error', [
                    'tracking_numbers' => $trackingNumbers,
                    'error' => $e->getMessage()
                ]);
            }

        });

        $this->info("Sync completed. Total synced: {$synced}");

        return 0;
    }
}