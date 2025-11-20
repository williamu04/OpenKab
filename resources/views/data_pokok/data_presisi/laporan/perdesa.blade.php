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
                            <label>{{ config('app.sebutanKab') }}</label>
                            <select name="Filter Kabupaten" id="filter_kabupaten" class="form-control form-control-sm"
                                title="Pilih {{ config('app.sebutanKab') }}">
                                <option value="">Pilih {{ config('app.sebutanKab') }}</option>
                            </select>
                        </div>
                        <div class="col-sm-3">
                            <label>Kecamatan</label>
                            <select name="Filter Kecamatan" id="filter_kecamatan" class="form-control form-control-sm"
                                title="Pilih Kecamatan">
                                <option value="">Pilih Kecamatan</option>
                            </select>
                        </div>
                        <div class="col-sm-3">
                            <label>{{ config('app.sebutanDesa') }}</label>
                            <select name="Filter Desa" id="filter_desa" class="form-control form-control-sm"
                                title="Pilih {{ config('app.sebutanDesa') }}">
                                <option value="">Pilih {{ config('app.sebutanDesa') }}</option>
                            </select>
                        </div>
                        {{-- <div class="col-sm-3">
                            <label>&nbsp;</label>
                            <button id="bt_filter" class="btn btn-sm btn-primary btn-block">Tampilkan</button>
                        </div> --}}
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

            let urlKabupaten =
                "{{ config('app.databaseGabunganUrl') . '/api/v1/statistik-web/get-list-kabupaten' }}";
            let urlKecamatan =
                "{{ config('app.databaseGabunganUrl') . '/api/v1/statistik-web/get-list-kecamatan' }}";
            let urlDesa = "{{ config('app.databaseGabunganUrl') . '/api/v1/statistik-web/get-list-desa' }}";

            // Load Kabupaten list
            $.get(urlKabupaten, {}, function(data) {
                for (var i = 0; i < data.length; i++) {
                    var newOption = new Option(data[i].nama_kabupaten, data[i].kode_kabupaten, false,
                        false);
                    $("#filter_kabupaten").append(newOption);
                }

                // Set default kabupaten dari session
                var defaultKabupaten = "{{ session('kabupaten.kode_kabupaten') ?? '' }}";
                if (defaultKabupaten) {
                    $('#filter_kabupaten').val(defaultKabupaten).trigger('change');
                }
            }, 'json');

            // Initialize Select2
            $('#filter_kabupaten').select2({
                placeholder: "Pilih {{ config('app.sebutanKab') }}",
                allowClear: true,
                width: '100%',
            });

            $('#filter_kecamatan').select2({
                placeholder: "Pilih Kecamatan",
                allowClear: true,
                width: '100%',
            });

            $('#filter_desa').select2({
                placeholder: "Pilih {{ config('app.sebutanDesa') }}",
                allowClear: true,
                width: '100%',
            });

            // Kabupaten change event
            // Flag untuk mencegah reload saat cascading change
            let preventReload = false;

            $('#filter_kabupaten').on('change', function() {
                let id = $(this).val();
                preventReload = true;
                $('#filter_kecamatan').empty().append(new Option("Pilih Kecamatan", "")).trigger("change");
                $('#filter_desa').empty().append(new Option("Pilih {{ config('app.sebutanDesa') }}", ""))
                    .trigger("change");
                preventReload = false;
                $('#filter_kecamatan').prop('disabled', true);

                if (id) {
                    $.ajax({
                        url: urlKecamatan + '/' + id,
                        type: 'GET',
                        dataType: 'json',
                    }).done(function(data) {
                        for (var i = 0; i < data.length; i++) {
                            var newOption = new Option(data[i].nama_kecamatan, data[i]
                                .kode_kecamatan, false, false);
                            $("#filter_kecamatan").append(newOption);
                        }
                        $('#filter_kecamatan').prop('disabled', false);

                        // Set default kecamatan dari session
                        var defaultKecamatan = "{{ session('kecamatan.kode_kecamatan') ?? '' }}";
                        if (defaultKecamatan) {
                            $('#filter_kecamatan').val(defaultKecamatan).trigger('change');
                        }
                    });
                }
            });

            // Kecamatan change event
            $('#filter_kecamatan').on('change', function() {
                let id = $(this).val();
                preventReload = true;
                $('#filter_desa').empty().append(new Option("Pilih {{ config('app.sebutanDesa') }}", ""))
                    .trigger("change");
                preventReload = false;
                $('#filter_desa').prop('disabled', true);

                if (id) {
                    $.ajax({
                        url: urlDesa + '/' + id,
                        type: 'GET',
                        dataType: 'json',
                    }).done(function(data) {
                        for (var i = 0; i < data.length; i++) {
                            var newOption = new Option(data[i].nama_desa, data[i].kode_desa, false,
                                false);
                            $("#filter_desa").append(newOption);
                        }
                        $('#filter_desa').prop('disabled', false);

                        // Set default desa dari session
                        var defaultDesa = "{{ session('desa.id') ?? '' }}";
                        if (defaultDesa) {
                            $('#filter_desa').val(defaultDesa);
                        }
                    });
                }
            });


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
                            "filter[config_id]": $('#filter_desa').val() ||
                                "{{ session('desa.id') ?? '' }}",
                            "kode_kabupaten": $('#filter_kabupaten').val() ||
                                "{{ session('kabupaten.kode_kabupaten') ?? '' }}",
                            "kode_kecamatan": $('#filter_kecamatan').val() ||
                                "{{ session('kecamatan.kode_kecamatan') ?? '' }}",
                            "config_desa": $('#filter_desa').val() || "{{ session('desa.id') ?? '' }}",
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

            // Auto reload hanya untuk filter desa (hanya saat user memilih manual)
            $('#filter_desa').on('change', function() {
                if (!preventReload && $(this).val()) {
                    laporanTable.ajax.reload();
                }
            });
        })
    </script>
@endsection
