<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProfileRequest;
use App\Models\Employee;
use App\Models\EmployeeDetail;
use App\Models\Log;
use App\Models\User;
use Hash;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProfilesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the user's profile.
     *
     * @return View
     */
    public function index()
    {
        $profile = auth()->user()->load('role', 'team');
        
        return view('pages.profile', compact('profile'));
    }

    /**
     * Update the user's profile.
     *
     * @param  StoreProfileRequest  $request
     * @param  User  $user
     * @return RedirectResponse
     */
    public function update(StoreProfileRequest $request, User $user): void        
    {
        // Cập nhật User
        $user->update([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
        ]);
    }
}
