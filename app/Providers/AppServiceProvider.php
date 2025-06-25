<?php

namespace App\Providers;

use App\Charts\AttendancesChart;
use App\Charts\PerformanceChart;
use App\Models\Access;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Blade;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrap();

        Blade::if('canEdit', function () {
            return session('access_level', 0) > 1;
        });

        Blade::if('canViewOnly', function () {
            return session('access_level', 0) == 1;
        });

        View::composer('*', function ($view) {
            if (auth()->check()) {
                $accesses = resolve(Access::class)->get(true);
                return $view->with('accesses', $accesses);
            }
        });
    }
}
