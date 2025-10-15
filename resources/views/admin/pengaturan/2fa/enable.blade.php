@extends('adminlte::page')

@section('title', 'Aktivasi Two-Factor Authentication')

@section('content_header')    
    <h1>Aktivasi Two-Factor Authentication</h1>       
@stop

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title">
                        <i class="fas fa-shield-alt mr-2"></i>
                        Aktivasi Two-Factor Authentication
                    </h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle mr-2"></i> Tentang Two-Factor Authentication (2FA)</h6>
                        <p class="mb-0">2FA menambahkan lapisan keamanan ekstra ke akun Anda. Setelah login dengan password atau OTP, Anda akan diminta untuk memasukkan kode verifikasi sekali pakai yang dikirim ke channel yang Anda pilih.</p>
                    </div>
                    
                    <form id="enable2faForm" action="{{ route('2fa.enable') }}" method="POST">
                        @csrf
                        @include('admin.pengaturan.2fa._form')
                        
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('2fa.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left mr-2"></i>Kembali
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane mr-2"></i>Kirim Kode Verifikasi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    @include('admin.pengaturan.2fa._styles')
@stop

@section('js')
    @include('admin.pengaturan.2fa._scripts')
@endsection