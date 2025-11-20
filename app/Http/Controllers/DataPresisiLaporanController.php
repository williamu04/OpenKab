<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DataPresisiLaporanController extends Controller
{
    public function index()
    {
        $title = 'Data Presisi Laporan Semua Desa';

        return view('data_pokok.data_presisi.laporan.index', compact('title'));
    }

    public function perdesa()
    {
        $title = 'Data Presisi Laporan Per Desa';

        return view('data_pokok.data_presisi.laporan.perdesa', compact('title'));
    }
}
