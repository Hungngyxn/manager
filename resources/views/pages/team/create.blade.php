@extends('layouts.admin', ['accesses' => $accesses, 'active' => 'team'])
@section('_content')
    <div class="container mt-3">
        <h4>Add New Team</h4>
        <form action="{{ route('team.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label class="form-label">Team</label>
                <input type="text" name="team" class="form-control" required>
            </div>
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Team
                </button>
                <a href="{{ route('team.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
@endsection