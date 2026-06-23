<div class="mb-3">
    <label>Registration Month</label>
    <div class="input-group flatpickr">
        <span class="input-group-text">
            <i class="fas fa-calendar-alt"></i>
        </span>
        <input type="date" name="thang_reg" class="form-control" placeholder="DD/MM/YYYY"
            value="{{ old('thang_reg', $account->thang_reg ?? '') }}" required>
    </div>

</div>
<div class="mb-3">
    <label>Account Age</label>
    <input type="text" name="tuoi_acc" class="form-control" value="{{ old('tuoi_acc', $account->tuoi_acc ?? '') }}"
        required>
</div>
<div class="mb-3">
    <label>Email</label>
    <input type="email" name="email" class="form-control" value="{{ old('email', $account->email ?? '') }}"
        required>
</div>
<div class="mb-3">
    <label>User</label>
    <select name="user_id" class="form-control select2" required>
        @foreach ($users as $user)
            <option value="{{ $user->id }}"
                {{ old('user_id', $account->user_id ?? '') == $user->id ? 'selected' : '' }}>
                {{ $user->name }}
            </option>
        @endforeach
    </select>
</div>
<div class="mb-3">
    <label>Status</label>
    <input type="text" name="status" class="form-control"
        value = " {{ old('status', $account->status ?? 'Active') }} " required>
</div>
<button type="submit" class="btn btn-primary">Save</button>


<script>
    document.addEventListener("DOMContentLoaded", function() {
        flatpickr("input[name='thang_reg']", {
            dateFormat: "Y-m-d",
            allowInput: true,
        });
    });

    $(document).ready(function() {
        $('.select2').select2({
            dropdownAutoWidth: true,
            width: '100%',
            theme: 'bootstrap-5',
            closeOnSelect: true
        });
    });
</script>
