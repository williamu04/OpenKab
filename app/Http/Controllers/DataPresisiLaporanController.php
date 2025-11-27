<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DataPresisiLaporanController extends Controller
{
    public function index()
    {
        $title = 'Data Presisi Pengisian Laporan Semua Desa';

        return view('data_pokok.data_presisi.laporan.index', compact('title'));
    }

    public function perdesa()
    {
        $title = 'Data Presisi Pengisian Laporan Per Desa';
        
        return view('data_pokok.data_presisi.laporan.perdesa', compact('title'));
    }
    
    public function cetak(Request $request)
    {
        $title = 'Data Presisi Pengisian Laporan Semua Desa';
        
        return view('data_pokok.data_presisi.laporan.cetak', ['filter' => $request->getQueryString(), 'title' => $title]);
    }

    public function cetakPerdesa(Request $request)
    {
        $title = 'Data Presisi Pengisian Laporan Per Desa';
        $namaDesa = session('desa.nama_desa') ?? 'Semua Desa';
        return view('data_pokok.data_presisi.laporan.cetak_perdesa', [
            'filter' => $request->getQueryString(), 
            'title' => $title,
            'namaDesa' => $namaDesa
        ]);
    }
}
