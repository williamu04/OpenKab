@extends('layouts.index')

@section('title', 'Pengaturan OTP & 2FA')

@section('content_header')
    <h1>Pengaturan OTP & 2FA Otentikasi</h1>
@stop

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            @include('admin.pengaturan.otp.partials.status-cards')
            
            @include('admin.pengaturan.otp.partials.otp-activation-form')
            
            @include('admin.pengaturan.otp.partials.2fa-activation-form')
            
            @include('admin.pengaturan.otp.partials.otp-verification-form')
            
            @include('admin.pengaturan.otp.partials.2fa-verification-form')
        </div>
        
        <div class="col-md-4">
            @include('admin.pengaturan.otp.partials.info-sidebar')
        </div>
    </div>
</div>
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

            // Show OTP activation form
            $('#enableOtpBtn').click(function() {
                $('#otpActivationSection').removeClass('d-none');
                $('#2faActivationSection, #otpVerificationSection, #2faVerificationSection').addClass('d-none');
                resetForm('otpSetupForm');
            });

            // Show 2FA activation form
            $('#enable2faBtn').click(function() {
                $('#2faActivationSection').removeClass('d-none');
                $('#otpActivationSection, #otpVerificationSection, #2faVerificationSection').addClass('d-none');
                resetForm('enable2faForm');
            });

            // Cancel OTP activation
            $('#cancelOtpActivation').click(function() {
                $('#otpActivationSection').addClass('d-none');
                resetForm('otpSetupForm');
            });

            // Cancel 2FA activation
            $('#cancel2faActivation').click(function() {
                $('#2faActivationSection').addClass('d-none');
                resetForm('enable2faForm');
            });

            // Cancel OTP verification
            $('#cancelOtpVerification').click(function() {
                $('#otpVerificationSection').addClass('d-none');
                resetForm('otpVerifyForm');
                clearInterval(countdownTimer);
                clearInterval(resendTimer);
            });

            // Cancel 2FA verification
            $('#cancel2faVerification').click(function() {
                $('#2faVerificationSection').addClass('d-none');
                resetForm('2faVerifyForm');
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
                            window.location.reload();
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
                            window.location.reload();
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

            // Resend OTP
            $('#resendOtpBtn').click(function() {
                resendCode('otp');
            });

            // Resend 2FA
            $('#resend2faBtn').click(function() {
                resendCode('2fa');
            });

            // Disable OTP
            $('#disableOtpBtn').click(function() {
                Swal.fire({
                    title: 'Nonaktifkan OTP?',
                    html: `
                        <div class="text-left">
                            <p><strong>Anda akan kehilangan:</strong></p>
                            <ul class="text-sm">
                                <li>Lapisan keamanan tambahan</li>
                                <li>Akses cepat tanpa password</li>
                                <li>Perlindungan dari serangan phishing</li>
                            </ul>
                            <p class="text-warning mt-3">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <strong>Apakah Anda yakin ingin melanjutkan?</strong>
                            </p>
                        </div>
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-times mr-2"></i>Ya, Nonaktifkan',
                    cancelButtonText: '<i class="fas fa-arrow-left mr-2"></i>Batal',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        const btn = $(this);
                        const originalText = btn.html();
                        
                        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Menonaktifkan...');

                        $.ajax({
                            url: '{{ route("otp.disable") }}',
                            type: 'POST',
                            data: { _token: '{{ csrf_token() }}' },
                            success: function(response) {
                                Swal.fire({
                                    title: 'OTP Dinonaktifkan!',
                                    text: response.message,
                                    icon: 'success',
                                    confirmButtonText: 'OK'
                                }).then(() => {
                                    window.location.reload();
                                });
                            },
                            error: function(xhr) {
                                const response = xhr.responseJSON;
                                Swal.fire({
                                    title: 'Gagal!',
                                    text: response.message || 'Gagal menonaktifkan OTP',
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                            },
                            complete: function() {
                                btn.prop('disabled', false).html(originalText);
                            }
                        });
                    }
                });
            });

            // Disable 2FA
            $('#disable2faBtn').click(function() {
                Swal.fire({
                    title: 'Nonaktifkan 2FA?',
                    html: `
                        <div class="text-left">
                            <p><strong>Anda akan kehilangan:</strong></p>
                            <ul class="text-sm">
                                <li>Lapisan keamanan tambahan (2FA)</li>
                                <li>Verifikasi identifikasi ganda</li>
                                <li>Perlindungan dari akses tidak sah</li>
                            </ul>
                            <p class="text-warning mt-3">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <strong>Apakah Anda yakin ingin melanjutkan?</strong>
                            </p>
                        </div>
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-times mr-2"></i>Ya, Nonaktifkan',
                    cancelButtonText: '<i class="fas fa-arrow-left mr-2"></i>Batal',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        const btn = $(this);
                        const originalText = btn.html();
                        
                        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Menonaktifkan...');

                        $.ajax({
                            url: '{{ route("2fa.disable") }}',
                            type: 'POST',
                            data: { _token: '{{ csrf_token() }}' },
                            success: function(response) {
                                Swal.fire({
                                    title: '2FA Dinonaktifkan!',
                                    text: response.message,
                                    icon: 'success',
                                    confirmButtonText: 'OK'
                                }).then(() => {
                                    window.location.reload();
                                });
                            },
                            error: function(xhr) {
                                const response = xhr.responseJSON;
                                Swal.fire({
                                    title: 'Gagal!',
                                    text: response.message || 'Gagal menonaktifkan 2FA',
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                            },
                            complete: function() {
                                btn.prop('disabled', false).html(originalText);
                            }
                        });
                    }
                });
            });

            // Auto-focus and format OTP input
            $('#otpCode, #2faCode').on('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value.length === 6) {
                    if ($(this).attr('id') === 'otpCode') {
                        $('#otpVerifyForm').submit();
                    } else {
                        $('#2faVerifyForm').submit();
                    }
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