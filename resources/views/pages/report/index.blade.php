@extends('layouts.admin', ['accesses' => $accesses, 'active' => 'report'])

@section('_content')
    <div class="container-fluid mt-2 px-4">
        {{-- Tiêu đề trang --}}
        <div class="row mb-3">
            <div class="col-12">
                <h4 class="font-weight-bold">Reports</h4>
                <hr>
            </div>
        </div>

        {{-- KHU VỰC THẺ BÁO CÁO (Theo hình ảnh đính kèm) --}}
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <a href="#"
                    class="btn btn-outline-primary w-100 p-3 text-start shadow-sm d-flex align-items-center h-100 transition-all">
                    <div class="me-3 p-2 bg-light text-primary rounded border">
                        <i class="fas fa-shopping-cart fa-lg"></i>
                    </div>
                    <div>
                        <span class="d-block font-weight-bold text-uppercase mb-1"
                            style="font-size: 0.95rem; letter-spacing: 0.5px;">Orders</span>
                        <span class="text-muted small d-block font-weight-normal">Report orders, amount, fee ... on
                            time</span>
                    </div>
                </a>
            </div>

            <div class="col-md-4 mb-3">
                <a href="#"
                    class="btn btn-outline-primary w-100 p-3 text-start shadow-sm d-flex align-items-center h-100 transition-all">
                    <div class="me-3 p-2 bg-light text-primary rounded border px-3">
                        <i class="fas fa-dollar-sign fa-lg"></i>
                    </div>
                    <div>
                        <span class="d-block font-weight-bold text-uppercase mb-1"
                            style="font-size: 0.95rem; letter-spacing: 0.5px;">Finance</span>
                        <span class="text-muted small d-block font-weight-normal">Finance by order status</span>
                    </div>
                </a>
            </div>

            <div class="col-md-4 mb-3">
                <a href="{{ route('report.seller') }}"
                    class="btn btn-outline-primary w-100 p-3 text-start shadow-sm d-flex align-items-center h-100 transition-all">
                    <div class="me-3 p-2 bg-light text-primary rounded border">
                        <i class="fas fa-users fa-lg"></i>
                    </div>
                    <div>
                        <span class="d-block font-weight-bold text-uppercase mb-1"
                            style="font-size: 0.95rem; letter-spacing: 0.5px;">Sellers</span>
                        <span class="text-muted small d-block font-weight-normal">Report order, profit... by seller</span>
                    </div>
                </a>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="/js/report.js"></script>
        <script>
            $(document).ready(function() {
                $('.select2').select2({
                    theme: 'bootstrap-5',
                    width: '100%'
                });
            });
        </script>
    @endpush
@endsection
