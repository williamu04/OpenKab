<?php

namespace Tests\Feature;

use Tests\BaseTestCase;

class JaminanSosialTest extends BaseTestCase
{
    /** @test */
    public function test_can_access_jaminan_sosial_page()
    {
        $response = $this->get(route('jaminan-sosial'));

        $response->assertStatus(200);
        $response->assertViewIs('data_pokok.jaminan_sosial.index');
        $response->assertViewHas('title', 'Data Kepesertaan Program dan Statistik');
    }

    /** @test */
    public function test_jaminan_sosial_page_has_required_elements()
    {
        $response = $this->get(route('jaminan-sosial'));
        $content = $response->getContent();

        // Test DataTable exists
        $this->assertStringContainsString('id="jaminanSosial"', $content, 'DataTable jaminanSosial tidak ditemukan');

        // Test filter tahun exists
        $this->assertStringContainsString('filter-tahun', $content, 'Filter tahun tidak ditemukan');

        // Test charts exist
        $this->assertStringContainsString('id="pie1"', $content, 'Chart pie1 tidak ditemukan');
        $this->assertStringContainsString('id="pie2"', $content, 'Chart pie2 tidak ditemukan');
        $this->assertStringContainsString('id="pie4"', $content, 'Chart pie4 tidak ditemukan');
    }

    /** @test */
    public function test_jaminan_sosial_has_correct_table_columns()
    {
        $response = $this->get(route('jaminan-sosial'));
        $content = $response->getContent();

        // Test table headers exist
        $expectedColumns = [
            'Aksi',
            'NIK',
            'Nama Kepala Keluarga',
            'Jumlah Anggota RTM',
            'Jenis Bantuan Sosial',
            'Jenis Gangguan Mental',
            'Jenis Penanganan',
        ];

        foreach ($expectedColumns as $column) {
            $this->assertStringContainsString($column, $content, "Kolom '{$column}' tidak ditemukan");
        }
    }

    /** @test */
    public function test_jaminan_sosial_has_print_button()
    {
        $response = $this->get(route('jaminan-sosial'));
        $content = $response->getContent();

        // Test print button rendered HTML exists (component is rendered to actual button)
        $this->assertStringContainsString('fa-print', $content, 'Icon print tidak ditemukan');
        $this->assertStringContainsString('jaminan-sosial/cetak', $content, 'Route print tidak ditemukan');
    }

    /** @test */
    public function test_jaminan_sosial_has_excel_download_button()
    {
        $response = $this->get(route('jaminan-sosial'));
        $content = $response->getContent();

        // Test excel download button rendered HTML exists (component is rendered to actual button)
        $this->assertStringContainsString('fa-file-excel', $content, 'Icon excel tidak ditemukan');
        $this->assertStringContainsString('btn-success', $content, 'Tombol Excel dengan class btn-success tidak ditemukan');
    }

    /** @test */
    public function test_jaminan_sosial_has_datatable_configuration()
    {
        $response = $this->get(route('jaminan-sosial'));
        $content = $response->getContent();

        // Test DataTable configuration
        $this->assertStringContainsString('processing: true', $content, 'DataTable processing config tidak ditemukan');
        $this->assertStringContainsString('serverSide: true', $content, 'DataTable serverSide config tidak ditemukan');
        $this->assertStringContainsString('ordering: false', $content, 'DataTable ordering config tidak ditemukan');

        // Test API endpoint
        $this->assertStringContainsString('/api/v1/data-presisi/jaminan-sosial', $content, 'API endpoint tidak ditemukan');
    }

    /** @test */
    public function test_jaminan_sosial_has_filter_tahun_functionality()
    {
        $response = $this->get(route('jaminan-sosial'));
        $content = $response->getContent();

        // Test filter tahun change event listener exists
        $this->assertStringContainsString("$('#filter-tahun').on('change'", $content, 'Event listener filter tahun tidak ditemukan');
        $this->assertStringContainsString('jaminanSosial.ajax.reload()', $content, 'DataTable reload pada filter tahun tidak ditemukan');
    }

    /** @test */
    public function test_jaminan_sosial_has_detail_control_functionality()
    {
        $response = $this->get(route('jaminan-sosial'));
        $content = $response->getContent();

        // Test detail control for expandable rows
        $this->assertStringContainsString('details-control', $content, 'Detail control class tidak ditemukan');
        $this->assertStringContainsString("jaminanSosial.on('click', 'td.details-control'", $content, 'Event listener detail control tidak ditemukan');
    }

    /** @test */
    public function test_jaminan_sosial_detail_button_has_correct_route()
    {
        $response = $this->get(route('jaminan-sosial'));
        $content = $response->getContent();

        // Test detail button route exists (rendered as actual URL)
        $this->assertStringContainsString('jaminan-sosial/detail', $content, 'Route detail tidak ditemukan');
    }

    /** @test */
    public function test_jaminan_sosial_uses_correct_api_filters()
    {
        $response = $this->get(route('jaminan-sosial'));
        $content = $response->getContent();

        // Test filter parameters in DataTable
        $this->assertStringContainsString('"filter[kode_desa]"', $content, 'Filter kode_desa tidak ditemukan');
        $this->assertStringContainsString('"filter[tahun]"', $content, 'Filter tahun tidak ditemukan');
        $this->assertStringContainsString('"filter[kepala_rtm]"', $content, 'Filter kepala_rtm tidak ditemukan');
        $this->assertStringContainsString("'include': 'anggota,penduduk,rtm,keluarga'", $content, 'Include relationships tidak ditemukan');
    }
}
