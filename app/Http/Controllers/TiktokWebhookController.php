<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TiktokWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {

        // Sau này xử lý theo loại sự kiện: order, cancel, refund...
        return response()->json(['status' => 'ok']);
    }
}