<?php

namespace App\Http\Controllers;

use App\Models\ErrorLog;
use App\Models\User;
use Illuminate\Http\Request;

class ErrorLogController extends Controller
{
    public function index(Request $request)
    {


        $query = ErrorLog::with(['order', 'user']);
        $sellers = User::all();

        // 📌 Filter theo seller
        if ($request->filled('user_id')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('user_id', $request->user_id);
            });
        }

        // 📌 Filter theo ngày
        if ($request->filled('date_start')) {
            $query->whereDate('created_at', '>=', $request->date_start);
        }
        if ($request->filled('date_end')) {
            $query->whereDate('created_at', '<=', $request->date_end);
        }

        $logs = $query->latest()->paginate(20);

        return view('pages.error_log', compact('logs', 'sellers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|string|max:255',
            'user_id' => 'nullable|integer',
            'error_message' => 'required|string|max:1000',
        ]);

        ErrorLog::create($validated);

        return redirect()
            ->route('pages.error_log')
            ->with('success', 'Log đã được thêm thành công!');
    }
}
