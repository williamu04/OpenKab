@extends('layouts.index')

@section('title', 'Pengaturan 2FA')

@section('content_header')
    <h1>Pengaturan 2FA</h1>
@stop

@section('content')

<!-- 2FA Activation Form -->
<div id="2faActivationSection">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h3 class="card-title">
                <i class="fas fa-shield-alt mr-2"></i>
                Aktivasi 2FA Otentikasi
            </h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" id="cancel2faActivation">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <h6><i class="fas fa-info-circle mr-2"></i> Tentang 2FA Otentikasi (2FA)</h6>
                <p class="mb-0">2FA menambahkan lapisan keamanan ekstra ke akun Anda. Setelah login dengan password atau OTP, Anda akan diminta untuk memasukkan kode verifikasi sekali pakai yang dikirim ke channel yang Anda pilih.</p>
            </div>
            
            <form id="enable2faForm" action="{{ route('2fa.enable') }}" method="POST">
                @csrf
                @include('admin.pengaturan.otp.partials._form')
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane mr-2"></i>Aktifkan 2FA
                </button>
            </form>
        </div>
    </div>
</div>
@include('admin.pengaturan.2fa.partials.verification-form')
@stop

@section('css')
    @include('admin.pengaturan.otp.partials._styles')    
@stop

@section('js')
    <script nonce="{{  csp_nonce() }}">
        // OTP Configuration from backend
        window.otpConfig = {!! json_encode([
            'expiresMinutes' => $otpConfig['expires_minutes'] ?? 5,
            'resendSeconds' => $otpConfig['resend_seconds'] ?? 30,
            'length' => $otpConfig['length'] ?? 6
        ]) !!}
        document.addEventListener("DOMContentLoaded", function (event) {
            let countdownTimer;
            let resendTimer;
            let currentProcess = null; // 'otp' or '2fa'

            // Toggle channel sections for both forms
            $('input[name="channel"]').change(function() {
                if ($(this).val() === 'email') {
                    $('#emailSection').removeClass('d-none');
                    $('#telegramSection').addClass('d-none');
                    $('#emailIdentifier').attr('name', 'identifier').attr('required', 'required');
                    $('#telegramIdentifier').removeAttr('name').removeAttr('required');
                } else {
                    $('#emailSection').addClass('d-none');
                    $('#telegramSection').removeClass('d-none');
                    $('#telegramIdentifier').attr('name', 'identifier').attr('required', 'required');
                    $('#emailIdentifier').removeAttr('name').removeAttr('required');
                }
            });

            $('input[name="channel"]').eq(0).trigger('change');                        

            // 2FA Setup form submission
            $('#enable2faForm').submit(function(e) {        
                e.preventDefault();
                e.stopPropagation();
                const btn = $(this).find('button[type="submit"]');
                const originalText = btn.html();
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Mengirim Kode...');
                
                $.ajax({
                    url: $(this).attr('action'),
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        console.log('AJAX Success:', response);
                        $('#2faActivationSection').addClass('d-none');
                        $('#2faVerificationSection').removeClass('d-none');
                        currentProcess = '2fa';
                        startCountdown(window.otpConfig.expiresMinutes * 60);
                        startResendCountdown(window.otpConfig.resendSeconds, '2fa');
                        setTimeout(() => {
                            if ($('#2faCode').length) $('#2faCode').focus();
                        }, 500);
                    },
                    error: function(xhr) {
                        console.log('AJAX Error:', xhr);
                        const response = xhr.responseJSON;
                        Swal.fire(
                            'Error!',
                            response?.message || 'Gagal mengirim kode aktivasi',
                            'error'
                        );
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });

            // 2FA Verify form submission
            $('#2faVerifyForm').submit(function(e) {
                e.preventDefault();
                const code = $('#2faCode').val();
                if (code.length !== 6) {
                    Swal.fire(
                        'Peringatan!',
                        'Kode 2FA harus 6 digit',
                        'warning'
                    );
                    return false;
                }
                const btn = $(this).find('button[type="submit"]');
                const originalText = btn.html();
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Memverifikasi...');
                
                $.ajax({
                    url: '{{ route("2fa.verify") }}',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        // Clear any timers
                        clearInterval(countdownTimer);
                        clearInterval(resendTimer);
                        Swal.fire(
                            'Berhasil!',
                            response.message,
                            'success'
                        ).then(() => {
                            window.location.href = '{{ route('otp.index')  }}';
                        });
                    },
                    error: function(xhr) {
                        const response = xhr.responseJSON;
                        Swal.fire(
                            'Error!',
                            response.message || 'Verifikasi gagal',
                            'error'
                        );
                        $('#2faCode').val('').focus();
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });

            // Resend 2FA
            $('#resend2faBtn').click(function() {
                resendCode('2fa');
            });

            // Auto-focus and format OTP input
            $('#2faCode').on('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value.length === 6) {
                    $('#2faVerifyForm').submit();                    
                }
            });

            function startCountdown(seconds) {
                let timeLeft = seconds;
                countdownTimer = setInterval(function() {
                    const minutes = Math.floor(timeLeft / 60);
                    const secs = timeLeft % 60;
                    const displayTime = minutes + ':' + (secs < 10 ? '0' : '') + secs;
                    
                    if (currentProcess === 'otp') {
                        $('#countdown').text(displayTime);
                    } else {
                        $('#2faCountdown').text(displayTime);
                    }
                    
                    if (timeLeft <= 0) {
                        clearInterval(countdownTimer);
                        if (currentProcess === 'otp') {
                            $('#countdown').text('Kedaluwarsa').addClass('text-danger');
                        } else {
                            $('#2faCountdown').text('Kedaluwarsa').addClass('text-danger');
                        }
                    }
                    timeLeft--;
                }, 1000);
            }

            function startResendCountdown(seconds, process) {
                let timeLeft = seconds;
                const button = process === 'otp' ? $('#resendOtpBtn') : $('#resend2faBtn');
                const countdownSpan = process === 'otp' ? $('#resendCountdownOtp') : $('#resendCountdown2fa');
                
                button.prop('disabled', true);
                
                resendTimer = setInterval(function() {
                    countdownSpan.text(timeLeft);
                    
                    if (timeLeft <= 0) {
                        clearInterval(resendTimer);
                        button.prop('disabled', false);
                        button.html('<i class="fas fa-redo mr-2"></i>Kirim Ulang');
                    }
                    timeLeft--;
                }, 1000);
            }

            function resendCode(process) {
                const btn = process === 'otp' ? $('#resendOtpBtn') : $('#resend2faBtn');
                const originalText = btn.html();
                
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Mengirim...');
                
                const url = process === 'otp' ? '{{ route("otp.resend") }}' : '{{ route("2fa.resend") }}';

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        Swal.fire(
                            'Berhasil!',
                            response.message,
                            'success'
                        );
                        startResendCountdown(window.otpConfig.resendSeconds, process);
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
            }

            function resetForm(formId) {
                $('#' + formId)[0].reset();
                $('input[name="channel"]').eq(0).prop('checked', true).trigger('change');
                $('#emailSection').removeClass('d-none');
                $('#telegramSection').addClass('d-none');
            }
        });
    </script>
@endsection