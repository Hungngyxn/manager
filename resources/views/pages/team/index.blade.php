@extends('layouts.admin', ['active' => 'team'])

@section('_content')
    <div class="container-fluid mt-2 px-4">
        <div class="row">
            <div class="col-12">
                <h4 class="font-weight-bold">Team Management</h4>
                <hr>
            </div>
        </div>

        {{-- Toolbar --}}
        <div class="row">
            <div class="col-12 mb-3">
                <div class="card bg-light shadow-sm p-4">

                    {{-- Toolbar --}}
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            @canEdit
                                {{-- Add Team Button (opens modal) --}}
                                <button class="btn btn-outline-dark px-4 py-2" data-bs-toggle="modal"
                                    data-bs-target="#addTeamModal">
                                    <i class="fas fa-plus me-2"></i> Add Team
                                </button>
                            @endcanEdit
                        </div>
                    </div>

                    {{-- Alerts --}}
                    @if (session('status'))
                        <div class="alert alert-info">
                            {!! session('status') !!}
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    {{-- Table --}}
                    <div class="table-responsive">
                        <table class="table table-light table-striped table-hover table-bordered text-center">
                            <thead class="table-light text-uppercase">
                                <tr>
                                    <th class="table-dark">#</th>
                                    <th class="table-dark">Team</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($teams as $team)
                                    <tr>
                                        <th scope="row">{{ $loop->iteration + $teams->firstItem() - 1 }}</th>
                                        <td>{{ $team->name }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        {{ $teams->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- Modal Add Team --}}
    <div class="modal fade" id="addTeamModal" tabindex="-1" aria-labelledby="addTeamModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('team.store') }}" method="POST" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="addTeamModalLabel">Add New Team</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label">Team Name</label>
                        <input type="text" name="name" class="form-control" required value="{{ old('name') }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Team
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Reopen modal if validation fails --}}
    @if ($errors->any())
        <script>
            window.addEventListener('load', () => {
                new bootstrap.Modal(document.getElementById('addTeamModal')).show();
            });
        </script>
    @endif
@endsection
