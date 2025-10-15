@extends('adminlte::page')

@section('title', 'Verifikasi Two-Factor Authentication')

@section('content_header')    
    <h1>Verifikasi Two-Factor Authentication</h1>       
@stop

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title">
                        <i class="fas fa-shield-check mr-2"></i>
                        Verifikasi Aktivasi 2FA
                    </h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle mr-2"></i> Informasi Verifikasi</h6>
                        <p class="mb-0">Kode verifikasi telah dikirim ke {{ $tempConfig['channel'] === 'email' ? 'email' : 'Telegram' }}: <strong>{{ $tempConfig['identifier'] }}</strong></p>
                    </div>
                    
                    <form id="verify2faForm" action="{{ route('2fa.verify') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="code"><strong>Masukkan Kode Verifikasi:</strong></label>
                            <input type="text" class="form-control text-center otp-input" id="code" name="code" placeholder="000000" maxlength="6" required autofocus>
                            <small class="form-text text-muted">Masukkan kode 6 digit yang telah dikirim</small>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <button type="button" class="btn btn-outline-secondary" id="backBtn">
                                <i class="fas fa-arrow-left mr-2"></i>Kembali
                            </button>
                            <div>
                                <button type="button" class="btn btn-outline-primary mr-2" id="resendBtn">
                                    <i class="fas fa-redo mr-2"></i>Kirim Ulang
                                </button>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-check mr-2"></i>Verifikasi & Aktifkan
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <div class="mt-3 text-center">
                        <small class="text-muted">
                            <i class="fas fa-clock mr-1"></i>
                            Kode akan kadaluarsa dalam <span id="countdown">5:00</span>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
<style>
    .card-header.bg-primary {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%) !important;
    }
    .otp-input {
        letter-spacing: 10px;
        font-size: 24px;
        font-family: 'Courier New', monospace;
        font-weight: bold;
    }
</style>
@stop

@section('js')
<script nonce="{{  csp_nonce() }}">
document.addEventListener("DOMContentLoaded", function (event) {
    let countdownTimer;
    let resendTimer;
    
    // Auto-focus and format OTP input
    $('#code').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
        if (this.value.length === 6) {
            $('#verify2faForm').submit();
        }
    });
    
    // Start countdown
    startCountdown(300); // 5 minutes in seconds
    
    // Verify form submission
    $('#verify2faForm').submit(function(e) {
        e.preventDefault();
        const code = $('#code').val();
        if (code.length !== 6) {
            Swal.fire(
                'Peringatan!',
                'Kode verifikasi harus 6 digit',
                'warning'
            );
            return false;
        }
        
        const btn = $(this).find('button[type="submit"]');
        const originalText = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Memverifikasi...');
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                Swal.fire(
                    'Berhasil!',
                    response.message,
                    'success'
                ).then(() => {
                    window.location.href = response.redirect;
                });
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                Swal.fire(
                    'Error!',
                    response.message || 'Verifikasi gagal',
                    'error'
                );
                $('#code').val('').focus();
            },
            complete: function() {
                btn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Back button
    $('#backBtn').click(function() {
        window.location.href = '{{ route("2fa.enable-form") }}';
    });
    
    // Resend OTP
    $('#resendBtn').click(function() {
        const btn = $(this);
        const originalText = btn.html();
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Mengirim...');
        
        $.ajax({
            url: '{{ route("2fa.resend") }}',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                Swal.fire(
                    'Berhasil!',
                    response.message,
                    'success'
                );
                startCountdown(300); // Reset countdown
                startResendCountdown(30); // 30 seconds cooldown
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                Swal.fire(
                    'Error!',
                    response.message || 'Gagal mengirim ulang',
                    'error'
                );
            },
            complete: function() {
                btn.html(originalText);
            }
        });
    });
    
    function startCountdown(seconds) {
        clearInterval(countdownTimer);
        let timeLeft = seconds;
        
        countdownTimer = setInterval(function() {
            const minutes = Math.floor(timeLeft / 60);
            const secs = timeLeft % 60;
            $('#countdown').text(minutes + ':' + (secs < 10 ? '0' : '') + secs);
            
            if (timeLeft <= 0) {
                clearInterval(countdownTimer);
                $('#countdown').text('Kedaluwarsa').addClass('text-danger');
            }
            timeLeft--;
        }, 1000);
    }
    
    function startResendCountdown(seconds) {
        clearInterval(resendTimer);
        let timeLeft = seconds;
        $('#resendBtn').prop('disabled', true);
        
        resendTimer = setInterval(function() {
            $('#resendBtn').html('<i class="fas fa-redo mr-2"></i>Kirim Ulang (' + timeLeft + ')');
            
            if (timeLeft <= 0) {
                clearInterval(resendTimer);
                $('#resendBtn').prop('disabled', false).html('<i class="fas fa-redo mr-2"></i>Kirim Ulang');
            }
            timeLeft--;
        }, 1000);
    }
});
</script>
@endsection