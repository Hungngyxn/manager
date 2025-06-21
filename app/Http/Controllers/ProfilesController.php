<?php

namespace App\Http\Controllers;

use App\Models\User;
use Hash;
use Illuminate\View\View;
use Illuminate\Http\Request;


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
    public function update(Request $request, User $user)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'password' => 'nullable|string|min:6',
            ]);

            $data = [
                'name' => $validated['name'],
                'email' => $validated['email'],
            ];

            if (!empty($validated['password'])) {
                $data['password'] = Hash::make($validated['password']);
            }
            $user->update($data);

            return redirect()->back()->with('status', 'Updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }
}
