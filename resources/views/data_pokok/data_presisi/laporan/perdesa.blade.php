@extends('layouts.index')

@section('title', $title)

@section('content_header')
    <h1>{{ $title }}</h1>
@stop

@push('css')
    <style>
        .details {
            margin-left: 20px;
        }
    </style>
@endpush

@section('content')
    @include('partials.breadcrumbs')
    <div class="row">
        <div class="col-lg-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <div class="row">
                        <div class="col-sm-3">
                            <button id="cetak" type="button" class="btn btn-primary btn-sm mt-4" data-url="">
                                <i class="fa fa-print"></i> Cetak
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="laporanTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Uraian</th>
                                    <th>Data Lengkap</th>
                                    <th>Lengkap Sebagian</th>
                                    <th>Tidak Lengkap</th>
                                    <th>Total Data</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script nonce="{{ csp_nonce() }}">
        document.addEventListener("DOMContentLoaded", function(event) {
            const header = @include('layouts.components.header_bearer_api_gabungan');
            var url = new URL("{{ config('app.databaseGabunganUrl') . '/api/v1/data-presisi/laporan-perdesa' }}");

            var laporanTable = $('#laporanTable').DataTable({
                processing: true,
                serverSide: true,
                searching: false,
                ajax: {
                    url: url.href,
                    type: 'GET',
                    headers: header,
                    data: function(row) {
                        // Konversi parameter DataTables ke format JSON:API
                        return {
                            "page[size]": row.length,
                            "page[number]": (row.start / row.length) + 1,
                            "filter[search]": row.search.value,
                            // "filter[config_id]": "{{ session('desa.id') ?? '' }}",
                            "kode_kabupaten": "{{ session('kabupaten.kode_kabupaten') ?? '' }}",
                            "kode_kecamatan": "{{ session('kecamatan.kode_kecamatan') ?? '' }}",
                            "config_desa": "{{ session('desa.id') ?? '' }}",
                            "sort": row.order[0].dir === 'desc' ? '-nama_desa' : 'nama_desa'
                        };
                    },
                    dataSrc: function(json) {
                        // Konversi response JSON:API ke format DataTables
                        json.recordsTotal = json.meta.pagination.total;
                        json.recordsFiltered = json.meta.pagination.total;

                        // Extract attributes dari data
                        return json.data.map(item => item.attributes);
                    }
                },
                columns: [{
                        data: null,
                        render: function(data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
                    {
                        data: 'uraian',
                    },
                    {
                        data: 'lengkap',
                        render: $.fn.dataTable.render.number('.', ',', 0, '')
                    },
                    {
                        data: 'sebagian',
                        render: $.fn.dataTable.render.number('.', ',', 0, '')
                    },
                    {
                        data: 'tidak_lengkap',
                        render: $.fn.dataTable.render.number('.', ',', 0, '')
                    },
                    {
                        data: 'total',
                        render: $.fn.dataTable.render.number('.', ',', 0, '')
                    },
                ]
            });

            $('#cetak').on('click', function() {
                let baseUrl = "{{ route('laporan.data-presisi.cetak-perdesa') }}";

                let params = $('#laporanTable').DataTable().ajax.params(); // Get DataTables params
                let queryString = new URLSearchParams(params).toString(); // Convert params to query string
                window.open(`${baseUrl}?${queryString}`, '_blank'); // Open the URL with appended query
            });
        })
    </script>
@endsection
