@extends('layouts.index')

@section('title', 'Pengaturan OTP')

@section('content_header')
    <h1>Pengaturan OTP</h1>
@stop

@section('content')
<!-- OTP Activation Form -->
<div id="otpActivationSection">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h3 class="card-title">
                <i class="fas fa-key mr-2"></i>
                Aktivasi OTP
            </h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" id="cancelOtpActivation">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <h6><i class="fas fa-info-circle mr-2"></i> Tentang OTP</h6>
                <p class="mb-0">OTP (One-Time Password) adalah metode login alternatif tanpa password. Setelah diaktifkan, Anda dapat login ke sistem hanya dengan kode sekali pakai yang dikirim ke channel yang Anda pilih.</p>
            </div>

            <form id="otpSetupForm" action="{{ route('otp.setup') }}" method="POST">
                @csrf
                @include('admin.pengaturan.otp.partials._form')
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane mr-2"></i>Aktifkan OTP
                </button>
            </form>
        </div>
    </div>
</div>
@include('admin.pengaturan.otp.partials.verification-form')
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

            // Cancel OTP activation
            $('#cancelOtpActivation').click(function() {
                $('#otpActivationSection').addClass('d-none');
                resetForm('otpSetupForm');
            });
            

            // Cancel OTP verification
            $('#cancelOtpVerification').click(function() {
                $('#otpVerificationSection').addClass('d-none');
                resetForm('otpVerifyForm');
                clearInterval(countdownTimer);
                clearInterval(resendTimer);
            });
            
            // OTP Setup form submission
            $('#otpSetupForm').submit(function(e) {        
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
                        $('#otpActivationSection').addClass('d-none');
                        $('#otpVerificationSection').removeClass('d-none');
                        currentProcess = 'otp';
                        startCountdown(window.otpConfig.expiresMinutes * 60);
                        startResendCountdown(window.otpConfig.resendSeconds, 'otp');
                        setTimeout(() => {
                            if ($('#otpCode').length) $('#otpCode').focus();
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
     
            // OTP Verify form submission
            $('#otpVerifyForm').submit(function(e) {
                e.preventDefault();
                const otp = $('#otpCode').val();
                if (otp.length !== 6) {
                    Swal.fire(
                        'Peringatan!',
                        'Kode OTP harus 6 digit',
                        'warning'
                    );
                    return false;
                }
                const btn = $(this).find('button[type="submit"]');
                const originalText = btn.html();
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Memverifikasi...');
                
                $.ajax({
                    url: '{{ route("otp.verify-activation") }}',
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
                            window.location.href = '{{ route('otp.index') }}';
                        });
                    },
                    error: function(xhr) {
                        const response = xhr.responseJSON;
                        Swal.fire(
                            'Error!',
                            response.message || 'Verifikasi gagal',
                            'error'
                        );
                        $('#otpCode').val('').focus();
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
           
            // Resend OTP
            $('#resendOtpBtn').click(function() {
                resendCode('otp');
            });           

            // Auto-focus and format OTP input
            $('#otpCode').on('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value.length === 6) {                    
                    $('#otpVerifyForm').submit();                    
                }
            });

            // Enhanced UI interactions for active state
            $('.info-box').hover(
                function() {
                    $(this).addClass('shadow');
                }, 
                function() {
                    $(this).removeClass('shadow');
                }
            );

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