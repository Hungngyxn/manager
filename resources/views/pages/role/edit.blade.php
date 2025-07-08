@extends('layouts.admin', ['accesses' => $accesses, 'active' => 'accounts'])

@section('_content')
    <div class="container-fluid mt-4">
        <div class="d-flex justify-content-center">
            <div class="card shadow-sm w-100" style="max-width: 80%;"> <!-- card nhỏ 80% như yêu cầu -->
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Edit Role</h5>
                    <a href="{{ route('roles.index') }}" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Back
                    </a>
                </div>

                <div class="card-body">
                    <form action="{{ route('roles.update', $role->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3 row">
                            <label for="name" class="col-sm-3 col-form-label fw-bold">Name</label>
                            <div class="col-sm-9">
                                <input type="text" name="name" id="name"
                                    class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name', $role->name) }}" placeholder="Enter role name" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-sm-3 col-form-label fw-bold">Superuser?</label>
                            <div class="col-sm-9 d-flex align-items-center">
                                <input type="hidden" name="is_super_user" value="0">
                                <div class="form-check form-switch">
                                    <input class="form-check-input @error('is_super_user') is-invalid @enderror"
                                        type="checkbox" id="is_super_user" name="is_super_user" value="1"
                                        {{ $role->is_super_user ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_super_user">
                                        Grant full access
                                    </label>
                                </div>
                                @error('is_super_user')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <hr class="my-4">

                        <h6 class="fw-bold mb-3">Access Permissions</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle text-center">
                                <thead class="table-light">
                                    <tr>
                                        <th>Menu</th>
                                        <th>Disabled</th>
                                        <th>View</th>
                                        <th>All</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($accessesForEditing as $access)
                                        <tr>
                                            <td class="text-start">{{ Str::title($access->menu->name) }}</td>
                                            <td>
                                                <input type="radio"
                                                    name="menuAndAccessLevel[{{ $loop->index }}][{{ $access->menu->id }}]"
                                                    value="0" {{ $access->status == 0 ? 'checked' : '' }} required>
                                            </td>
                                            <td>
                                                <input type="radio"
                                                    name="menuAndAccessLevel[{{ $loop->index }}][{{ $access->menu->id }}]"
                                                    value="1" {{ $access->status == 1 ? 'checked' : '' }}>
                                            </td>
                                            <td>
                                                <input type="radio"
                                                    name="menuAndAccessLevel[{{ $loop->index }}][{{ $access->menu->id }}]"
                                                    value="2" {{ $access->status == 2 ? 'checked' : '' }}>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 text-end">
                            <button type="submit" class="btn btn-primary px-5">
                                <i class="fas fa-save me-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const superuserCheckbox = document.getElementById("is_super_user");
                const accessRadios = document.querySelectorAll("input[type=radio]");

                function setAllAccessTo(value) {
                    accessRadios.forEach(radio => {
                        if (radio.value == value) {
                            radio.checked = true;
                        }
                    });
                }

                superuserCheckbox.addEventListener("change", function() {
                    if (this.checked) {
                        setAllAccessTo(2); // Set all to 'All'
                    }
                });

                accessRadios.forEach(radio => {
                    radio.addEventListener("change", function() {
                        if (this.value != '2') {
                            superuserCheckbox.checked = false;
                        }
                    });
                });
            });
        </script>
    @endpush
@endsection
