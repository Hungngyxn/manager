@extends('layouts.admin', ['accesses' => $accesses, 'active' => 'accounts'])

@section('_content')
    <div class="container-fluid mt-2 px-4">
        <div class="row">
            <div class="col-12">
                <h4 class="fw-bold fs-4">Role</h4>
                <hr>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <h5 class="text-center fw-bold mb-3 fs-5">Create A New Role</h5>
                <form action="{{ route('roles.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="name" class="fw-bold fs-5">Role Name</label>
                                    <input type="text" name="name" id="name"
                                        class="form-control @error('name') is-invalid @enderror fs-6"
                                        value="{{ old('name') }}" placeholder="Enter role name" required>
                                </div>
                                @error('name')
                                    <div class="alert alert-danger fs-6">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12">
                                <div class="form-check">
                                    <input type="hidden" name="is_super_user" value="0">
                                    <input type="checkbox"
                                        class="form-check-input @error('is_super_user') is-invalid @enderror"
                                        id="is_super_user" name="is_super_user" value="1"
                                        {{ old('is_super_user' ? 'checked' : '') }}>
                                    <label class="form-check-label fs-6" for="is_super_user">Superuser?</label>
                                </div>
                                @error('is_super_user')
                                    <div class="alert alert-danger fs-6">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <h6 class="fw-bold mb-2 fs-5">Access Permissions</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle text-center">
                                <thead class="table-light">
                                    <tr>
                                        <th class="fs-6">Menu</th>
                                        <th class="fs-6">Disabled</th>
                                        <th class="fs-6">View</th>
                                        <th class="fs-6">All</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($menus as $menu)
                                        <tr>
                                            <td class="text-start fs-6">{{ Str::title($menu->name) }}</td>
                                            <td>
                                                <input type="radio"
                                                    name="menuAndAccessLevel[{{ $loop->index }}][{{ $menu->id }}]"
                                                    id="{{ $menu->name }}_disabled" value="0" required
                                                    {{ old("menuAndAccessLevel[$loop->index][$menu->id]") == '0' ? 'checked' : '' }}>
                                            </td>
                                            <td>
                                                <input type="radio"
                                                    name="menuAndAccessLevel[{{ $loop->index }}][{{ $menu->id }}]"
                                                    id="{{ $menu->name }}_view" value="1"
                                                    {{ old("menuAndAccessLevel[$loop->index][$menu->id]") == '1' ? 'checked' : '' }}>
                                            </td>
                                            <td>
                                                <input type="radio"
                                                    name="menuAndAccessLevel[{{ $loop->index }}][{{ $menu->id }}]"
                                                    id="{{ $menu->name }}_all" value="2"
                                                    {{ old("menuAndAccessLevel[$loop->index][$menu->id]") == '2' ? 'checked' : '' }}>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary px-5">
                                <i class="fas fa-save me-1"></i> Save
                            </button>
                        </div>
                    </div>
                </form>
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
    </div>
@endsection
