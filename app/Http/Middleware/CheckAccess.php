<?php

namespace App\Http\Middleware;

use App\Models\Access;
use App\Models\Menu;
use Closure;
use Illuminate\Http\Request;

class CheckAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $name = explode(".", $request->route()->getName())[0];

        if ($name == "user") {
            $name = "user";
        } else if ($name == "roles") {
            $name = "role";
        } else if ($name == "orders") {
            $name = "order";
        } else if ($name == "account") {
            $name = "account";
        } else if ($name == "shops") {
            $name = "shop";
        } else if ($name == "profile") {
            $name = "account";
        } else if ($name == "sku") {
            $name = "sku";
        } else if ($name == "sku-orders") {
            $name = "sku";
        } else if ($name == "team") {
            $name = "team";
        } else if ($name == "report") {
            $name = "report";
        } else if ($name == "ads-fee") {
            $name = "report";
        } else if ($name == "tiktok") {
            $name = "shop";
        } else if ($name == "shop-accounts") {
            $name = "shop";
        } else if ($name == "shopus") {
            $name = "shop";
        } else if ($name == "log") {
            $name = "log";
        } else if ($name == "bonus") {
            $name = "bonus";
        }

        $menuId = Menu::whereName($name)->first()->id;
        $accessType = Access::where([
            ["menu_id", '=', $menuId],
            ["role_id", '=', auth()->user()->role_id],
        ])->first()->status;

        if ($accessType < 1) {
            return redirect()->route('dashboard');
        }

        session()->put('access_level', $accessType);

        return $next($request);
    }
}
