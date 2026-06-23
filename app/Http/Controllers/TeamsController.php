<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Illuminate\Http\Request;

class TeamsController extends Controller
{
    public function index()
    {
        $teams = Team::query()->paginate(10);

        return view('pages.team.index', compact('teams'));
    }

    public function create()
    {
        return view('pages.team.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|min:3|max:255',
        ]);

        Team::create([
            'name' => 'Team ' . $request->name,
        ]);
        
        return redirect()->route('team.index')->with('success', 'Team created successfully');
    }

    public function destroy($id)
    {
        $team = Team::findOrFail($id);

        $team->delete();

        return redirect()->route('team.index')->with('status', 'Team deleted successfully.');
    }
}
