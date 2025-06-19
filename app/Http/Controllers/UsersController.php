<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\Team;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $query = User::with('role');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        $users = $query->paginate(10)->appends($request->only('search'));
        $teams = Team::all();

        return view('pages.user.index', compact('users', 'teams'));
    }

    public function create()
    {
        $roles = Role::all();
        $teams = Team::all();

        return view('pages.user.create', compact('roles', 'teams'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'role_id' => 'required|exists:roles,id',
            'team_id' => 'required|exists:teams,id'
        ]);

        DB::beginTransaction();

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role_id' => $request->role_id,
                'team_id' => $request->team_id,
                'status' => true,
            ]);

            DB::commit();

            return redirect()->route('user.index')->with('status', 'User created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->route('user.index')->with('error', 'Failed to create user: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $user = User::with('role')->findOrFail($id);
        
        return view('pages.user.show', compact('user'));
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        $roles = Role::all();

        return view('pages.user.edit', compact('user', 'roles'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|unique:users,email,' . $id,
            'role_id' => 'required|exists:roles,id',
            'password' => 'nullable|string|min:6',
        ]);

        DB::beginTransaction();

        try {
            $user = User::findOrFail($id);
            $attributes = $request->only('name', 'email', 'role_id');

            if ($request->filled('password')) {
                $attributes['password'] = Hash::make($request->password);
            }

            $user->update($attributes);

            DB::commit();

            return redirect()->route('user.index')->with('status', 'User updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->route('user.index')->with('error', 'Failed to update user: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            User::findOrFail($id)->delete();

            return redirect()->route('user.index')->with('status', 'User deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->route('user.index')->with('error', 'Failed to delete user: ' . $e->getMessage());
        }
    }

    public function updateTeam(Request $request, User $user)
    {
        $request->validate([
            'team_id' => 'nullable|exists:teams,id',
        ]);

        $user->team_id = $request->team_id;
        $user->save();

        return back()->with('status', 'Cập nhật team thành công!');
    }

    public function activate($id)
    {
        try {
            $user = User::findOrFail($id);
            $user->status = true;
            $user->save();

            return redirect()->route('user.index')->with('status', 'User activated successfully.');
        } catch (\Exception $e) {
            return redirect()->route('user.index')->with('error', 'Failed to activate user: ' . $e->getMessage());
        }
    }

    public function deactivate($id)
    {
        try {
            $user = User::findOrFail($id);
            $user->status = false;
            $user->save();

            return redirect()->route('user.index')->with('status', 'User deactivated successfully.');
        } catch (\Exception $e) {
            return redirect()->route('user.index')->with('error', 'Failed to deactivate user: ' . $e->getMessage());
        }
    }
}
