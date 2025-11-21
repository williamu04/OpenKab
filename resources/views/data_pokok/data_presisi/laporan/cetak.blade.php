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
    <table class="border thick" id="tabel-laporan">
        <thead>
            <tr class="border thick">
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
@stop

@push('scripts')
    <script nonce="{{ csp_nonce() }}">
        document.addEventListener("DOMContentLoaded", function(event) {
            var str = `{{ $filter }}`
            var filter = str.replace(/&amp;/g, '&')
            const header = @include('layouts.components.header_bearer_api_gabungan');
            $.ajax({
                url: `{{ config('app.databaseGabunganUrl') . '/api/v1/data-presisi/laporan' }}?${filter}`,
                headers: header,
                method: 'get',
                success: function(json) {
                    var no = 1;
                    json.data.forEach(function(item) {
                        var attr = item.attributes;
                        var row = `
                                <tr>
                                    <td class="padat">${no}</td>
                                    <td>${attr.desa || 'N/A'}</td>
                                    <td>${attr.pangan || 'N/A'}</td>
                                    <td>${attr.sandang || 'N/A'}</td>
                                    <td>${attr.papan || 'N/A'}</td>
                                    <td>${attr.pendidikan || 'N/A'}</td>
                                    <td>${attr.seni_budaya || 'N/A'}</td>
                                    <td>${attr.kesehatan || 'N/A'}</td>
                                    <td>${attr.keagamaan || 'N/A'}</td>
                                    <td>${attr.jaminan_sosial || 'N/A'}</td>
                                    <td>${attr.adat || 'N/A'}</td>
                                    <td>${attr.ketenagakerjaan || 'N/A'}</td>
                                    <td>${attr.jumlah_penduduk !== undefined ? attr.jumlah_penduduk.toLocaleString('id-ID') : 'N/A'}</td>
                                </tr>
                            `;
                        $('#tabel-laporan tbody').append(row)
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
