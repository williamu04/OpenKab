@extends('adminlte::page')

@section('title', 'Pengaturan Two-Factor Otentikasi')

@section('content_header')    
    <h1>Pengaturan Two-Factor Otentikasi (2FA)</h1>       
@stop

@section('content')
    <div class="row">
        <div class="col-md-8">
            @if(!$twoFactorStatus['enabled'])
                <!-- Form Aktivasi 2FA -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title">
                            <i class="fas fa-shield-alt mr-2"></i>
                            Aktivasi Two-Factor Otentikasi
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle mr-2"></i> Tentang Two-Factor Otentikasi (2FA)</h6>
                            <p class="mb-0">2FA menambahkan lapisan keamanan ekstra ke akun Anda. Setelah login dengan password atau OTP, Anda akan diminta untuk memasukkan kode verifikasi sekali pakai yang dikirim ke channel yang Anda pilih.</p>
                        </div>
                        
                        <form id="enable2faForm" action="{{ route('2fa.enable') }}" method="POST">
                            @csrf
                            @include('admin.pengaturan.2fa._form')
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane mr-2"></i>Aktifkan 2FA
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <!-- Status 2FA Aktif -->
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h3 class="card-title">
                            <i class="fas fa-shield-check mr-2"></i>
                            Status 2FA: <span class="badge badge-light text-success ml-2">AKTIF</span>
                        </h3>
                    </div>
                    <div class="card-body">                      
                        <!-- Informasi Detail 2FA -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="info-box">
                                    <h6 class="text-primary"><i class="fas fa-broadcast-tower mr-2"></i><strong>Channel Aktif:</strong></h6>
                                    <div class="mt-2">
                                        @foreach($twoFactorStatus['channel'] as $channel)
                                            <span class="badge badge-primary badge-lg mr-2 mb-1">
                                                @if($channel === 'email')
                                                    <i class="fas fa-envelope mr-1"></i> Email
                                                @else
                                                    <i class="fab fa-telegram mr-1"></i> Telegram
                                                @endif
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-box">
                                    <h6 class="text-primary"><i class="fas fa-id-card mr-2"></i><strong>Identifier:</strong></h6>
                                    <div class="mt-2">
                                        <code class="bg-light p-2 rounded">{{ $twoFactorStatus['identifier'] }}</code>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-box">
                                    <h6 class="text-primary"><i class="fas fa-calendar-check mr-2"></i><strong>Status:</strong></h6>
                                    <div class="mt-2">
                                        <small class="text-success d-block">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            Aktif sejak {{ $user->updated_at->diffForHumans() }}
                                        </small>
                                        <small class="text-muted">
                                            {{ $user->updated_at->format('d M Y, H:i') }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Status Keamanan -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="alert alert-success border-0">
                                    <h6 class="text-success mb-2"><i class="fas fa-shield-alt mr-2"></i>Status Keamanan Maksimal</h6>
                                    <div class="row text-sm">
                                        <div class="col-md-4">
                                            <i class="fas fa-check text-success mr-2"></i>
                                            <strong>Password Otentikasi</strong>
                                        </div>
                                        <div class="col-md-4">
                                            <i class="fas fa-check text-success mr-2"></i>
                                            <strong>OTP Otentikasi</strong>
                                        </div>
                                        <div class="col-md-4">
                                            <i class="fas fa-check text-success mr-2"></i>
                                            <strong>2FA Otentikasi</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>
                        
                        <!-- Cara Menggunakan -->
                        <div class="mb-4">
                            <h6 class="text-primary"><i class="fas fa-question-circle mr-2"></i><strong>Proses Login dengan 2FA:</strong></h6>
                            <ol class="text-sm pl-3">
                                <li>Login dengan password atau OTP seperti biasa</li>
                                <li>Setelah berhasil login, Anda akan diarahkan ke halaman verifikasi 2FA</li>
                                <li>Kode verifikasi akan dikirim ke {{ $twoFactorStatus['channel'][0] === 'email' ? 'email' : 'Telegram' }}: <code>{{ $twoFactorStatus['identifier'] }}</code></li>
                                <li>Masukkan kode 6 digit untuk menyelesaikan proses login</li>
                                <li>Setelah verifikasi berhasil, Anda dapat mengakses dashboard</li>
                            </ol>
                        </div>

                        <!-- Tombol Aksi -->
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    2FA aktif untuk semua metode login (password dan OTP)
                                </small>
                            </div>
                            <div>
                                <button type="button" class="btn btn-outline-warning" id="disable2faBtn">
                                    <i class="fas fa-times-circle mr-2"></i>
                                    Nonaktifkan 2FA
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-md-4">
            @if(!$twoFactorStatus['enabled'])
                @include('admin.pengaturan.2fa._info')
            @else
                @include('admin.pengaturan.2fa._status')
            @endif
        </div>
    </div>
@stop

@section('css')
    @include('admin.pengaturan.2fa._styles')
@stop

@section('js')
    @include('admin.pengaturan.2fa._scripts')
@endsection