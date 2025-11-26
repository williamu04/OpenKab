@extends('layouts.cetak.index')

@section('title', $title)

@push('css')
    <style nonce="{{ csp_nonce() }}" type="text/css" media="print">
        @page {
            size: landscape;
        }
    </style>
@endpush

@section('content')
    @include('partials.breadcrumbs')
    {{-- @dd($filter) --}}
    <div style="margin-bottom: 15px;">
        <h3 style="margin: 0; font-weight: bold;">Desa: {{ $namaDesa }}</h3>
    </div>
    <table class="border thick" id="tabel-laporan-perdesa">
        <thead>
            <tr class="border thick">
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
@stop

@push('scripts')
    <script nonce="{{ csp_nonce() }}">
        document.addEventListener("DOMContentLoaded", function(event) {
            var str = `{{ $filter }}`
            var filter = str.replace(/&amp;/g, '&')
            const header = @include('layouts.components.header_bearer_api_gabungan');
            $.ajax({
                url: `{{ config('app.databaseGabunganUrl') . '/api/v1/data-presisi/laporan-perdesa' }}?${filter}`,
                headers: header,
                method: 'get',
                success: function(json) {
                    var no = 1;
                    json.data.forEach(function(item) {
                        var attr = item.attributes;
                        var row = `
                                <tr>
                                    <td class=\"padat\">${no}</td>
                                    <td>${attr.uraian || 'N/A'}</td>
                                    <td>${attr.lengkap !== undefined ? attr.lengkap.toLocaleString('id-ID') : '0'}</td>
                                    <td>${attr.sebagian !== undefined ? attr.sebagian.toLocaleString('id-ID') : '0'}</td>
                                    <td>${attr.tidak_lengkap !== undefined ? attr.tidak_lengkap.toLocaleString('id-ID') : '0'}</td>
                                    <td>${attr.total !== undefined ? attr.total.toLocaleString('id-ID') : '0'}</td>
                                </tr>
                            `;
                        $('#tabel-laporan-perdesa tbody').append(row)
                        no++;
                    });
                    // Trigger print after data is loaded
                    window.print();
                },
                error: function(xhr, status, error) {
                    console.error('Error loading data:', error);
                    alert('Gagal memuat data. Silakan coba lagi.');
                }
            })
        });
    </script>
@endpush
