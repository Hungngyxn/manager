<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Models\Access;
use App\Models\Admin;
use App\Models\Log;
use App\Models\Menu;
use App\Models\Role;
use Illuminate\Http\Request;

class RolesController extends Controller
{
    private $roles;

    public function __construct()
    {
        $this->middleware('auth');

        $this->roles = resolve(Role::class);
    }

    public function index()
    {
        $roles = $this->roles->paginate(10);

        return view('pages.role.index', compact('roles'));
    }

    public function create()
    {
        $menus = Menu::all();

        return view('pages.role.create', compact('menus'));
    }

    public function store(StoreRoleRequest $request)
    {
        $roleId = Role::create(['name' => $request->input('name')])->id;

        if ($request->input('is_super_user') == "1") {
            Admin::create([
                'role_id' => $roleId
            ]);
        }

        foreach ($request->menuAndAccessLevel as $mna) {
            $key = key($mna);
            Access::create([
                'role_id' => $roleId,
                'menu_id' => $key,
                'status' => $mna[$key]
            ]);
        }

        return redirect()->route('roles.index')->with('status', 'Successfully created a role.');
    }

    public function show(Role $role)
    {
        $accessesForEditing = Access::where('role_id', $role->id)->with('menu', 'role')->orderBy('menu_id', 'ASC')->get();

        return view('pages.role.show', compact('accessesForEditing', 'role'));
    }

    public function edit(Role $role)
    {
        $accessesForEditing = Access::where('role_id', $role->id)->with('menu', 'role')->orderBy('menu_id', 'ASC')->get();
        
        return view('pages.role.edit', compact('accessesForEditing', 'role'));
    }

    public function update(StoreRoleRequest $request, Role $role)
    {
        $role->update([
            'name' => $request->input('name'),
            'is_super_user' => $request->input('is_super_user'),
        ]);

        if ($request->input('is_super_user') == "0") {
            Admin::whereRoleId($role->id)->delete();
        }

        foreach ($request->menuAndAccessLevel as $mna) {
            $key = key($mna);
            Access::where([
                ['role_id', '=', $role->id],
                ['menu_id', '=', $key],
            ])->update([
                        'status' => $mna[$key]
                    ]);
        }

        return redirect()->route('roles.index')->with('status', 'Successfully updated role.');
    }

    public function destroy(Role $role)
    {
        $this->roles->where('id', $role->id)->delete();

        return redirect()->route('roles.index')->with('status', 'Successfully deleted role.');
    }
}
