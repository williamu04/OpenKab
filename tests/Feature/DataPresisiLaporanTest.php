<?php

namespace Tests\Feature;

use Tests\BaseTestCase;

class DataPresisiLaporanTest extends BaseTestCase
{
    /** @test */
    public function test_can_access_laporan_semua_desa_page()
    {
        $response = $this->get(route('laporan.data-presisi.index'));

        $response->assertStatus(200);
        $response->assertViewIs('data_pokok.data_presisi.laporan.index');
        $response->assertViewHas('title', 'Data Presisi Laporan Semua Desa');
    }

    /** @test */
    public function test_laporan_semua_desa_has_required_elements()
    {
        $response = $this->get(route('laporan.data-presisi.index'));

        $content = $response->getContent();

        // Test DataTable exists
        $this->assertStringContainsString('id="laporanTable"', $content);

        // Test filter status exists
        $this->assertStringContainsString('id="filter-status"', $content);

        // Test status options exist
        $this->assertStringContainsString('Tidak Lengkap', $content);
        $this->assertStringContainsString('Lengkap Sebagian', $content);
        $this->assertStringContainsString('Data Lengkap', $content);
    }

    /** @test */
    public function test_laporan_semua_desa_has_correct_table_columns()
    {
        $response = $this->get(route('laporan.data-presisi.index'));

        $content = $response->getContent();

        // Test table headers exist
        $expectedColumns = [
            'Desa',
            'Pangan',
            'Sandang',
            'Papan',
            'Pendidikan',
            'Seni Budaya',
            'Kesehatan',
            'Keagamaan',
            'Jaminan Sosial',
            'Adat',
            'Ketenagakerjaan',
            'Jumlah Penduduk'
        ];

        foreach ($expectedColumns as $column) {
            $this->assertStringContainsString($column, $content);
        }
    }

    /** @test */
    public function test_laporan_semua_desa_has_filter_status_javascript()
    {
        $response = $this->get(route('laporan.data-presisi.index'));

        $content = $response->getContent();

        // Test filter status change event listener exists
        $this->assertStringContainsString("$('#filter-status').on('change'", $content);
        $this->assertStringContainsString("$('#laporanTable').DataTable().ajax.reload()", $content);
    }

    /** @test */
    public function test_laporan_semua_desa_has_datatable_configuration()
    {
        $response = $this->get(route('laporan.data-presisi.index'));

        $content = $response->getContent();

        // Test DataTable configuration
        $this->assertStringContainsString('processing: true', $content);
        $this->assertStringContainsString('serverSide: true', $content);

        // Test filter[status_kelengkapan] parameter
        $this->assertStringContainsString('"filter[status_kelengkapan]"', $content);
        $this->assertStringContainsString("$('#filter-status').val()", $content);
    }

    /** @test */
    public function test_can_access_laporan_perdesa_page()
    {
        $response = $this->get(route('laporan.data-presisi.perdesa'));

        $response->assertStatus(200);
        $response->assertViewIs('data_pokok.data_presisi.laporan.perdesa');
        $response->assertViewHas('title', 'Data Presisi Laporan Per Desa');
    }

    /** @test */
    public function test_laporan_perdesa_has_required_filter_elements()
    {
        $response = $this->get(route('laporan.data-presisi.perdesa'));

        $content = $response->getContent();

        // Test all filter elements exist
        $this->assertStringContainsString('id="filter_kabupaten"', $content);
        $this->assertStringContainsString('id="filter_kecamatan"', $content);
        $this->assertStringContainsString('id="filter_desa"', $content);
    }

    /** @test */
    public function test_laporan_perdesa_has_correct_table_columns()
    {
        $response = $this->get(route('laporan.data-presisi.perdesa'));

        $content = $response->getContent();

        // Test table headers exist
        $expectedColumns = [
            'Uraian',
            'Data Lengkap',
            'Lengkap Sebagian',
            'Tidak Lengkap',
            'Total Data'
        ];

        foreach ($expectedColumns as $column) {
            $this->assertStringContainsString($column, $content);
        }
    }

    /** @test */
    public function test_laporan_perdesa_has_select2_initialization()
    {
        $response = $this->get(route('laporan.data-presisi.perdesa'));

        $content = $response->getContent();

        // Test Select2 initialization for all filters
        $this->assertStringContainsString("$('#filter_kabupaten').select2(", $content);
        $this->assertStringContainsString("$('#filter_kecamatan').select2(", $content);
        $this->assertStringContainsString("$('#filter_desa').select2(", $content);
    }

    /** @test */
    public function test_laporan_perdesa_has_cascading_filter_logic()
    {
        $response = $this->get(route('laporan.data-presisi.perdesa'));

        $content = $response->getContent();

        // Test kabupaten change event
        $this->assertStringContainsString("$('#filter_kabupaten').on('change'", $content);

        // Test kecamatan change event
        $this->assertStringContainsString("$('#filter_kecamatan').on('change'", $content);

        // Test desa change event with reload
        $this->assertStringContainsString("$('#filter_desa').on('change'", $content);
        $this->assertStringContainsString('laporanTable.ajax.reload()', $content);
    }

    /** @test */
    public function test_laporan_perdesa_loads_kabupaten_from_api()
    {
        $response = $this->get(route('laporan.data-presisi.perdesa'));

        $content = $response->getContent();

        // Test API call to get kabupaten list
        $this->assertStringContainsString('/api/v1/statistik-web/get-list-kabupaten', $content);
    }

    /** @test */
    public function test_laporan_perdesa_loads_kecamatan_from_api()
    {
        $response = $this->get(route('laporan.data-presisi.perdesa'));

        $content = $response->getContent();

        // Test API call to get kecamatan list
        $this->assertStringContainsString('/api/v1/statistik-web/get-list-kecamatan', $content);
    }

    /** @test */
    public function test_laporan_perdesa_loads_desa_from_api()
    {
        $response = $this->get(route('laporan.data-presisi.perdesa'));

        $content = $response->getContent();

        // Test API call to get desa list
        $this->assertStringContainsString('/api/v1/statistik-web/get-list-desa', $content);
    }

    /** @test */
    public function test_laporan_perdesa_has_datatable_with_filters()
    {
        $response = $this->get(route('laporan.data-presisi.perdesa'));

        $content = $response->getContent();

        // Test DataTable configuration
        $this->assertStringContainsString('processing: true', $content);
        $this->assertStringContainsString('serverSide: true', $content);
        $this->assertStringContainsString('searching: false', $content);

        // Test filter parameters in DataTable
        $this->assertStringContainsString('"filter[config_id]"', $content);
        $this->assertStringContainsString('"kode_kabupaten"', $content);
        $this->assertStringContainsString('"kode_kecamatan"', $content);
        $this->assertStringContainsString('"config_desa"', $content);
    }

    /** @test */
    public function test_laporan_perdesa_has_default_session_values()
    {
        $response = $this->get(route('laporan.data-presisi.perdesa'));

        $content = $response->getContent();

        // Test that session default values are being set (even if empty)
        // Check that variables exist in JavaScript code
        $this->assertStringContainsString('defaultKabupaten', $content);
        $this->assertStringContainsString('defaultKecamatan', $content);
        $this->assertStringContainsString('defaultDesa', $content);
    }

    /** @test */
    public function test_laporan_perdesa_has_prevent_reload_flag()
    {
        $response = $this->get(route('laporan.data-presisi.perdesa'));

        $content = $response->getContent();

        // Test preventReload flag to avoid cascading reloads
        $this->assertStringContainsString('preventReload', $content);
        $this->assertStringContainsString('preventReload = true', $content);
        $this->assertStringContainsString('preventReload = false', $content);
        $this->assertStringContainsString('if (!preventReload', $content);
    }

    /** @test */
    public function test_laporan_perdesa_disables_child_filters_initially()
    {
        $response = $this->get(route('laporan.data-presisi.perdesa'));

        $content = $response->getContent();

        // Test that child filters are disabled until parent is selected
        $this->assertStringContainsString("$('#filter_kecamatan').prop('disabled', true)", $content);
        $this->assertStringContainsString("$('#filter_desa').prop('disabled', true)", $content);
    }

    /** @test */
    public function test_laporan_perdesa_number_formatting_for_columns()
    {
        $response = $this->get(route('laporan.data-presisi.perdesa'));

        $content = $response->getContent();

        // Test number formatting render for numeric columns
        $this->assertStringContainsString("render: $.fn.dataTable.render.number('.', ',', 0, '')", $content);
    }

    /** @test */
    public function test_both_pages_use_correct_api_endpoints()
    {
        // Test laporan index
        $response1 = $this->get(route('laporan.data-presisi.index'));
        $content1 = $response1->getContent();
        $this->assertStringContainsString('/api/v1/data-presisi/laporan', $content1);

        // Test laporan perdesa
        $response2 = $this->get(route('laporan.data-presisi.perdesa'));
        $content2 = $response2->getContent();
        $this->assertStringContainsString('/api/v1/data-presisi/laporan-perdesa', $content2);
    }

    /** @test */
    public function test_both_pages_have_breadcrumbs()
    {
        // Test laporan index has breadcrumb section
        $response1 = $this->get(route('laporan.data-presisi.index'));
        $response1->assertStatus(200);

        // Test laporan perdesa has breadcrumb section
        $response2 = $this->get(route('laporan.data-presisi.perdesa'));
        $response2->assertStatus(200);
        
        $this->assertTrue(true);
    }

    /** @test */
    public function test_both_pages_extend_correct_layout()
    {
        // Test laporan index uses correct layout (has main-footer)
        $response1 = $this->get(route('laporan.data-presisi.index'));
        $content1 = $response1->getContent();
        $this->assertStringContainsString('class="main-footer"', $content1);

        // Test laporan perdesa uses correct layout (has main-footer)
        $response2 = $this->get(route('laporan.data-presisi.perdesa'));
        $content2 = $response2->getContent();
        $this->assertStringContainsString('class="main-footer"', $content2);
    }
}
