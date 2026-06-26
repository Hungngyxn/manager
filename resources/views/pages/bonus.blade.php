@extends('layouts.admin', ['accesses' => $accesses, 'active' => 'bonus'])

@section('_content')
    <div class="container-fluid mt-2 px-4">
        <div class="row">
            <div class="col-12">
                <h4 class="font-weight-bold">Bonus</h4>
                <hr>
            </div>
        </div>

        {{-- Select Seller --}}
        <div class="mb-3 d-flex align-items-center gap-2">
            <label for="sellerSelect" class="form-label fw-bold mb-0">Chọn Seller</label>
            <select id="sellerSelect" class="form-select w-auto d-inline-block">
                <option value="all">Show All</option>
                @foreach ($sellers as $seller)
                    <option value="{{ Str::slug($seller['name'], '_') }}">{{ $seller['name'] }}</option>
                @endforeach
            </select>
        </div>

        {{-- Tables --}}
        <div id="sellerContainer">
            @foreach ($sellers as $seller)
                <div class="seller-table mb-4" data-seller="{{ Str::slug($seller['name'], '_') }}">
                    <h5 class="fw-bold">{{ $seller['name'] }}</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered text-center align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>%Tier</th>
                                    <th>BaseCost</th>
                                    <th>BaseCost Refund</th>
                                    <th>Fulfill Fee</th>
                                    <th>Ads</th>
                                    <th>Paid</th>
                                    <th>Bonus</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($seller['tiers'] as $tier)
                                    <tr>
                                        <td>{{ $tier['tier'] }}</td>
                                        <td>{{ $tier['base_cost'] }}</td>
                                        <td>{{ $tier['refund'] }}</td>
                                        <td>{{ $tier['fulfill_fee'] }}</td>
                                        <td>{{ $tier['ads'] }}</td>
                                        <td>{{ $tier['paid'] }}</td>
                                        <td>{{ $tier['bonus'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <nav>
            <ul class="pagination" id="pagination"></ul>
        </nav>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const sellerSelect = document.getElementById('sellerSelect');
                const sellerTables = document.querySelectorAll('.seller-table');
                const pagination = document.getElementById('pagination');
                const perPage = 5;
                let currentPage = 1;

                function renderPagination(totalPages) {
                    pagination.innerHTML = '';
                    for (let i = 1; i <= totalPages; i++) {
                        let li = document.createElement('li');
                        li.className = 'page-item ' + (i === currentPage ? 'active' : '');
                        li.innerHTML = `<a class="page-link" href="#">${i}</a>`;
                        li.addEventListener('click', (e) => {
                            e.preventDefault();
                            currentPage = i;
                            showPage();
                        });
                        pagination.appendChild(li);
                    }
                }

                function showPage() {
                    let selected = sellerSelect.value;
                    let tables = Array.from(sellerTables);

                    // Reset: show/hide dựa vào select
                    tables.forEach(table => {
                        if (selected === 'all') {
                            table.style.display = 'none'; // sẽ xử lý theo trang sau
                        } else {
                            table.style.display = (table.dataset.seller === selected) ? 'block' : 'none';
                        }
                    });

                    if (selected === 'all') {
                        let totalPages = Math.ceil(tables.length / perPage);
                        renderPagination(totalPages);

                        let start = (currentPage - 1) * perPage;
                        let end = start + perPage;

                        tables.forEach((table, index) => {
                            if (index >= start && index < end) {
                                table.style.display = 'block';
                            }
                        });
                    } else {
                        pagination.innerHTML = ''; // clear pagination nếu chọn seller cụ thể
                    }
                }

                // Event select change
                sellerSelect.addEventListener('change', function() {
                    currentPage = 1; // reset về page 1
                    showPage();
                });

                // Init
                showPage();
            });
        </script>
    @endpush
@endsection
