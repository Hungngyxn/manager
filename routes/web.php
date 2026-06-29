<?php

use App\Http\Controllers\AdsFeeController;
use App\Http\Controllers\BonusController;
use App\Http\Controllers\ErrorLogController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductListController;
use App\Http\Controllers\Report\ReportController;
use App\Http\Controllers\Report\ReportSellerController;
use App\Http\Controllers\SellerHasShopController;
use App\Http\Controllers\ShopAccountController;
use App\Http\Controllers\ShopUsController;
use App\Http\Controllers\SkuController;
use App\Http\Controllers\SkuOrderController;
use App\Http\Controllers\TeamsController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfilesController;
use App\Http\Controllers\RolesController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Auth::routes([
    'register' => false,
    'verify' => false,
    'reset' => false
]);


Route::middleware(['auth', 'check.access', 'check.status'])->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/top-sellers', [DashboardController::class, 'getTopSellers'])->name('dashboard.top_sellers');

    Route::prefix('tiktok')->name('tiktok.')->group(function () {
        Route::get('/connect', [SellerHasShopController::class, 'connectTikTok'])->name('connect');
        Route::get('/shop/{id}/reconnect', [SellerHasShopController::class, 'reconnectTikTok'])->name('reconnect');
        Route::get('/shop/{id}/finance', [SellerHasShopController::class, 'financeShop'])->name('finance');
        Route::get('/callback', [SellerHasShopController::class, 'tiktokCallback'])->name('callback');
    });

    // Users
    Route::prefix('user')->name('user.')->group(function () {
        Route::get('/', [UsersController::class, 'index'])->name('index');
        Route::get('/create', [UsersController::class, 'create'])->name('create');
        Route::post('/', [UsersController::class, 'store'])->name('store');
        Route::put('/user/{user}/update-team', [UsersController::class, 'updateTeam'])->name('updateTeam');
        Route::get('/{user}', [UsersController::class, 'show'])->name('show');
        Route::get('/{user}/edit', [UsersController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UsersController::class, 'update'])->name('update');
        Route::delete('/{user}', [UsersController::class, 'destroy'])->name('destroy');
        Route::patch('/user/{id}/activate', [UsersController::class, 'activate'])->name('activate');
        Route::patch('/user/{id}/deactivate', [UsersController::class, 'deactivate'])->name('deactivate');

    });

    // Roles
    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/', [RolesController::class, 'index'])->name('index');
        Route::get('/create', [RolesController::class, 'create'])->name('create');
        Route::post('/', [RolesController::class, 'store'])->name('store');
        Route::get('/{role}', [RolesController::class, 'show'])->name('show');
        Route::get('/{role}/edit', [RolesController::class, 'edit'])->name('edit');
        Route::put('/{role}', [RolesController::class, 'update'])->name('update');
        Route::delete('/{role}', [RolesController::class, 'destroy'])->name('destroy');
    });

    // Profile
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfilesController::class, 'index'])->name('index');
        Route::get('/profile/password', [ProfilesController::class, 'editPassword'])->name('profile.password.edit');
        Route::put('/profile/password', [ProfilesController::class, 'updatePassword'])->name('profile.password.update');
        Route::put('/{user}', [ProfilesController::class, 'update'])->name('update');
    });

    //Order
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/create', [OrderController::class, 'create'])->name('create');
        Route::post('/', [OrderController::class, 'store'])->name('store');
        Route::post('/import', [OrderController::class, 'import'])->name('import');
        Route::post('/import-fulfill-fee', [OrderController::class, 'importFulfillFee'])->name('import.fulfill_fee');
        Route::post('/export', [OrderController::class, 'export'])->name('export');
        Route::delete('/delete', [OrderController::class, 'delete'])->name('delete');
        Route::get('/sample-file', [OrderController::class, 'downloadSample'])->name('download-sample');
        Route::get('/sync', [OrderController::class, 'sync'])->name('sync');

        // Lưu print info (modal "Print setup") cho đơn ShopUS, khớp theo shop_us.order_id
        Route::post('/{orderId}/save-print-info', [ShopUsController::class, 'savePrintInfo'])->name('save-print-info');

        Route::get('/{order}', [OrderController::class, 'show'])->name('show');
        Route::get('/{order}/edit', [OrderController::class, 'edit'])->name('edit');
        Route::put('/{order}', [OrderController::class, 'update'])->name('update');
        Route::delete('/{order}', [OrderController::class, 'destroy'])->name('destroy');
    });

    // Shop
    Route::prefix('shop')->name('shop.')->group(function () {
        Route::get('/', [SellerHasShopController::class, 'index'])->name('index');
        Route::get('/create', [SellerHasShopController::class, 'create'])->name('create');
        Route::post('/', [SellerHasShopController::class, 'store'])->name('store');
        Route::post('/check-seller', [SellerHasShopController::class, 'checkSeller'])->name('check_seller');
        Route::get('/sample-file', [SellerHasShopController::class, 'downloadSample'])->name('download-sample');
        Route::delete('/{shop}', [SellerHasShopController::class, 'destroy'])->name('destroy');
        Route::put('/{shop}', [SellerHasShopController::class, 'update'])->name('update');
        Route::get('/{shop}/edit', [SellerHasShopController::class, 'edit'])->name('edit');
        Route::post('/import', [SellerHasShopController::class, 'importShop'])->name('import');
        Route::get('/exportBalance', [SellerHasShopController::class, 'exportBalance'])->name('exportBalance');
    });

    // Sku
    Route::prefix('sku')->name('sku.')->group(function () {
        Route::get('/', [SkuController::class, 'index'])->name('index');
        Route::get('/create', [SkuController::class, 'create'])->name('create');
        Route::post('/import', [SkuController::class, 'import'])->name('import');
        Route::post('/', [SkuController::class, 'store'])->name('store');
        Route::get('/{sku}/edit', [SkuController::class, 'edit'])->name('edit');
        Route::put('/{sku}', [SkuController::class, 'update'])->name('update');
        Route::delete('/{sku}', [SkuController::class, 'destroy'])->name('destroy');
    });

    //sku-order

    Route::prefix('sku-order')->name('sku-orders.')->group(function () {
        Route::get('/', [SkuOrderController::class, 'index'])->name('index');
        Route::get('/create', [SkuOrderController::class, 'create'])->name('create');
        Route::post('/import', [SkuOrderController::class, 'import'])->name('import');
        Route::post('/', [SkuOrderController::class, 'store'])->name('store');
        Route::get('/{sku}/edit', [SkuOrderController::class, 'edit'])->name('edit');
        Route::put('/{sku}', [SkuOrderController::class, 'update'])->name('update');
        Route::delete('/{sku}', [SkuOrderController::class, 'destroy'])->name('destroy');
    });

    //Team
    Route::prefix('team')->name('team.')->group(function () {
        Route::get('/', [TeamsController::class, 'index'])->name('index');
        Route::get('/create', [TeamsController::class, 'create'])->name('create');
        Route::post('/', [TeamsController::class, 'store'])->name('store');
    });

    //Report
    Route::prefix('report')->name('report.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/update-ads', [ReportSellerController::class, 'initDailyReports'])->name('update');
        //seller
        Route::get('/seller', [ReportSellerController::class, 'index'])->name('seller');

    });

    Route::prefix('shop-accounts')->name('shop-accounts.')->group(function () {
        Route::get('/', [ShopAccountController::class, 'index'])->name('index');
        Route::get('/create', [ShopAccountController::class, 'create'])->name('create');
        Route::post('/', [ShopAccountController::class, 'store'])->name('store');
        Route::post('/import', [ShopAccountController::class, 'importShop'])->name('import');
        Route::delete('/{account}', [ShopAccountController::class, 'destroy'])->name('destroy');
        Route::put('/{account}', [ShopAccountController::class, 'update'])->name('update');
        Route::get('/{account}/edit', [ShopAccountController::class, 'edit'])->name('edit');
        Route::post('/batch-assign', [ShopAccountController::class, 'batchAssign'])->name('batch-assign');
    });

    Route::prefix('shopus')->name('shopus.')->group(function () {
        Route::get('/', [ShopUsController::class, 'index'])->name('index');
        Route::post('/update', [ShopUsController::class, 'update'])->name('update');
        Route::get('/create-label', [ShopUsController::class, 'syncOrdersWithLabel'])->name('createLabel');
        Route::get('/label/{id}', [ShopUsController::class, 'downloadLabel'])->name('label.download');
        Route::get('/get-label', [ShopUsController::class, 'runSyncPendingOrdersStatus'])->name('getLabel');
        Route::post('/export-selected', [ShopUsController::class, 'exportSelected'])
            ->name('export.selected');
    });

    Route::prefix('log')->name('log.')->group(function () {
        Route::get('/', [ErrorLogController::class, 'index'])->name('index');
        Route::get('/create', [ErrorLogController::class, 'create'])->name('create');
        Route::post('/', [ErrorLogController::class, 'store'])->name('store');
    });

    Route::prefix('bonus')->name('bonus.')->group(function () {
        Route::get('/', [BonusController::class, 'index'])->name('index');
    });

    Route::post('/ads-fee', [AdsFeeController::class, 'store'])->name('ads-fee.store');
    Route::get('/ads-fee/fetch', [AdsFeeController::class, 'fetch'])->name('ads-fee.fetch');
    Route::post('/ads-fee/import', [AdsFeeController::class, 'import'])->name('ads-fee.import');

    Route::post('/product-mapping/import', [ProductListController::class, 'import'])->name('product-mapping.import');

    Route::prefix('track')->name('track.')->group(function () {
        Route::get('/', [TrackingController::class, 'index'])->name('index');
        Route::get('/create', [TrackingController::class, 'create'])->name('create');
        Route::post('/', [TrackingController::class, 'store'])->name('store');
        Route::delete('/{id}', [TrackingController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/sync', [TrackingController::class, 'syncOrder'])->name('sync');
        Route::post('/syncAll', [TrackingController::class, 'syncAllOrders'])->name('syncAll');
    });
});
