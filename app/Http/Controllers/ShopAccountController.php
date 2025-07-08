<?php

namespace App\Http\Controllers;

use App\Imports\ShopAccountImport;
use App\Models\SellerHasShop;
use App\Models\ShopAccount;
use App\Models\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class ShopAccountController extends Controller
{
    public function index(Request $request)
    {
        $query = ShopAccount::with('user');

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%$search%")
                    ->orWhereHas('user', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%$search%");
                    });
            });
        }

        $accounts = $query->paginate(50)->withQueryString();    
        $allUsers = User::all();

        return view('pages.shop_account.index', compact('accounts', 'allUsers'));
    }

    public function create()
    {
        try {
            $users = User::all();

            return view('pages.shop_account.create', compact('users'));
        } catch (\Exception $e) {
            Log::error('Failed to load create page: ' . $e->getMessage());

            return back()->with('error', 'Failed to load create page.');
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'thang_reg' => 'required|date',
                'tuoi_acc' => 'required',
                'email' => 'required|email|unique:shop_accounts,email',
                'user_id' => 'required|exists:users,id',
                'status' => 'required',
            ]);

            ShopAccount::create([
                'thang_reg' => $request->thang_reg,
                'tuoi_acc' => $request->tuoi_acc,
                'email' => $request->email,
                'user_id' => $request->user_id,
                'status' => $request->status ?? 'active',
            ]);

            SellerHasShop::updateOrCreate(
                ['email' => $request->email],
                [
                    'user_id' => $request->user_id,
                    'email' => $request->email
                ]
            );

            return redirect()->route('shop-accounts.index')
                ->with('status', 'Account created successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to create shop account: ' . $e->getMessage());

            return back()->with('error', 'Failed to create account.')->withInput();
        }
    }

    public function edit($id)
    {
        try {
            $account = ShopAccount::findOrFail($id);
            $users = User::all();

            return view('pages.shop_account.edit', compact('account', 'users'));
        } catch (\Exception $e) {
            Log::error('Failed to load edit page: ' . $e->getMessage());

            return back()->with('error', 'Failed to load edit page.');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $account = ShopAccount::findOrFail($id);

            $request->validate([
                'thang_reg' => 'required|date',
                'tuoi_acc' => 'required',
                'email' => 'required|email|unique:shop_accounts,email,' . $id,
                'user_id' => 'required|exists:users,id',
                'status' => 'required',
            ]);

            $account->update([
                'thang_reg' => $request->thang_reg,
                'tuoi_acc' => $request->tuoi_acc,
                'email' => $request->email,
                'user_id' => $request->user_id,
                'status' => $request->status ?? 'active',
            ]);

            SellerHasShop::updateOrCreate(
                ['email' => $request->email],
                [
                    'user_id' => $request->user_id,
                    'email' => $request->email
                ]
            );

            return redirect()->route('shop-accounts.index')
                ->with('status', 'Account updated successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to update shop account: ' . $e->getMessage());

            return back()->with('error', 'Failed to update account.')->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $account = ShopAccount::findOrFail($id);
            $account->delete();

            return redirect()->route('shop-accounts.index')
                ->with('status', 'Account deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to delete shop account: ' . $e->getMessage());

            return back()->with('error', 'Failed to delete account.');
        }
    }

    public function importShop(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        $importer = new ShopAccountImport();

        try {
            Excel::import($importer, $request->file('file'));

            return redirect()->route('shop-accounts.index')
                ->with('status', count($importer->created) . ' rows imported successfully.')
                ->with('skipped', $importer->skipped);
        } catch (\Exception $e) {
            Log::error('Failed to import shop accounts: ' . $e->getMessage());

            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function storeFromExtension(Request $request)
    {
        try {
            return response()->json([
                'status' => 'success',
                'data' => $request->all(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed in storeFromExtension: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process the request.',
            ], 500);
        }
    }

    public function batchAssign(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'shop_ids' => 'required|array',
        ]);

        $userId = $request->user_id;
        $shopIds = $request->shop_ids;

        $accounts = ShopAccount::whereIn('id', $shopIds)->get();

        foreach ($accounts as $acc) {
            $acc->update(['user_id' => $userId]);

            SellerHasShop::where('email', $acc->email)->update([
                'user_id' => $userId,
            ]);
        }

        return redirect()->route('shop-accounts.index')
            ->with('status', 'Batch assign completed successfully.');
    }
}
