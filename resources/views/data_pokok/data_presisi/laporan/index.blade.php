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
                        <div class="col-sm-2">
                            <select id="filter-status" class="form-control form-control-sm">
                                @php
                                    $statusOptions = [
                                        0 => 'Tidak Lengkap',
                                        1 => 'Lengkap Sebagian',
                                        2 => 'Data Lengkap',
                                    ];
                                @endphp
                                @foreach ($statusOptions as $key => $label)
                                    <option value="{{ $key }}" @selected($key == 2)>{{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="laporanTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Desa</th>
                                    <th>Pangan</th>
                                    <th>Sandang</th>
                                    <th>Papan</th>
                                    <th>Pendidikan</th>
                                    <th>Seni Budaya</th>
                                    <th>Kesehatan</th>
                                    <th>Keagamaan</th>
                                    <th>Jaminan Sosial</th>
                                    <th>Adat</th>
                                    <th>Ketenagakerjaan</th>
                                    <th>Jumlah Penduduk</th>
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
            var url = new URL("{{ config('app.databaseGabunganUrl') . '/api/v1/data-presisi/laporan' }}");
            url.searchParams.set("kode_kabupaten", "{{ session('kabupaten.kode_kabupaten') ?? '' }}");
            url.searchParams.set("kode_kecamatan", "{{ session('kecamatan.kode_kecamatan') ?? '' }}");
            url.searchParams.set("kode_desa", "{{ session('desa.id') ?? '' }}");
            console.log(url);


            $('#laporanTable').DataTable({
                processing: true,
                serverSide: true,
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
                            "filter[status_kelengkapan]": $('#filter-status').val(),
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
                        data: 'desa',
                    },
                    {
                        data: 'pangan'
                    },
                    {
                        data: 'sandang'
                    },
                    {
                        data: 'papan'
                    },
                    {
                        data: 'pendidikan'
                    },
                    {
                        data: 'seni_budaya'
                    },
                    {
                        data: 'kesehatan'
                    },
                    {
                        data: 'keagamaan'
                    },
                    {
                        data: 'jaminan_sosial'
                    },
                    {
                        data: 'adat'
                    },
                    {
                        data: 'ketenagakerjaan'
                    },
                    {
                        data: 'jumlah_penduduk',
                        render: $.fn.dataTable.render.number('.', ',', 0, '')
                    }
                ]
            });

            // Event listener for year filter change
            $('#filter-status').on('change', function() {
                $('#laporanTable').DataTable().ajax.reload();
            });
        })
    </script>
@endsection
