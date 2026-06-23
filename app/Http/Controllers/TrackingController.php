<?php

namespace App\Http\Controllers;

use App\Models\TrackOrder;
use App\Models\User;
use App\Services\SaigonApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class TrackingController extends Controller
{
    public function index(Request $request)
    {
        $query = TrackOrder::with('seller')->orderBy('date', 'desc');

        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        if ($request->filled('from_date')) {
            $query->whereDate('date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('date', '<=', $request->to_date);
        }

        if ($request->filled('seller_id')) {
            $query->where('seller_id', $request->seller_id);
        }

        $tracks = $query->paginate(10);

        $sellers = User::whereIn(
            'id',
            TrackOrder::whereNotNull('seller_id')
                ->distinct()
                ->pluck('seller_id')
        )->get();

        $allSellers = User::get();

        return view('pages.track', [
            'tracks' => $tracks,
            'sellers' => $sellers,
            'allSellers' => $allSellers,
            'date_start' => $startDate->format('Y-m-d'),
            'date_end' => $endDate->format('Y-m-d'),
        ]);
    }


    /**
     * CREATE ORDER + REGISTER TRACKING
     */
    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'seller_id' => 'required|exists:users,id',
            'status' => 'required|string',
            'email' => 'nullable|email',
            'tracking_number' => 'nullable|string',
            'driver_link' => 'nullable|string',
            'sku' => 'required|string',
            'carrier' => 'required|string'
        ]);

        $order = TrackOrder::create($request->all());

        // register tracking lên Track123
        if ($order->tracking_number) {

            try {

                $service = new SaigonApiService();

                $service->registerTracking(
                    $order->tracking_number,
                    $request->carrier ?? 'auto'
                );

            } catch (\Throwable $e) {

                \Log::error('Track123 register failed', [
                    'tracking' => $order->tracking_number,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return redirect()
            ->route('track.index')
            ->with('success', 'Tracking record created successfully.');
    }


    public function destroy($id)
    {
        $track = TrackOrder::findOrFail($id);
        $track->delete();

        return redirect()
            ->route('track.index')
            ->with('success', 'Tracking record deleted.');
    }


    /**
     * SYNC ALL TRACKING STATUS
     */
    public function syncAllOrders(Request $request)
    {
        $service = new SaigonApiService();
        $synced = 0;

        TrackOrder::where(function ($q) {
            $q->where('delivered_mail_sent', false)
                ->orWhereNull('delivered_mail_sent');
        })
            ->whereNotNull('tracking_number')
            ->chunk(50, function ($orders) use ($service, &$synced) {

                $trackingNumbers = $orders->pluck('tracking_number')->toArray();
                try {

                    $data = $service->queryTracking($trackingNumbers);

                    if (!isset($data['data']['accepted']['content'])) {
                        return;
                    }


                    foreach ($data['data']['accepted']['content'] as $trackData) {

                        $trackNo = $trackData['trackNo'] ?? null;

                        if (!$trackNo) {
                            continue;
                        }

                        $order = $orders->firstWhere('tracking_number', $trackNo);

                        if (!$order) {
                            continue;
                        }

                        $detail = $trackData['localLogisticsInfo']['trackingDetails'][0] ?? null;

                        if (!$detail) {
                            continue;
                        }

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

                    \Log::error('Track123 sync error', [
                        'tracking_numbers' => $trackingNumbers,
                        'error' => $e->getMessage()
                    ]);
                }

            });

        return back()->with('success', "Sync xong {$synced} orders");
    }
}