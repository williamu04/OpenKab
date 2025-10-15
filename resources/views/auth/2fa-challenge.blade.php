<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Verifikasi Two-Factor Authentication</title>
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <link rel="stylesheet" href="{{ asset('vendor/adminlte_dist/css/adminlte.min.css') }}">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-box {
            width: 400px;
            max-width: 90%;
        }
        .login-card {
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            border-radius: 15px;
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-bottom: none;
            text-align: center;
            padding: 30px 20px;
        }
        .otp-input {
            letter-spacing: 8px;
            font-size: 28px;
            font-family: 'Courier New', monospace;
            font-weight: bold;
            text-align: center;
            height: 60px;
            border-radius: 10px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        .otp-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-verify {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            transition: all 0.3s ease;
        }
        .btn-verify:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .logo-icon {
            font-size: 60px;
            color: white;
            margin-bottom: 15px;
        }
        .shield-animation {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>
<div class="login-box">
    <div class="card login-card">
        <div class="card-header">
            <div class="logo-icon shield-animation">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h3 class="text-white mb-0">Verifikasi Two-Factor Authentication</h3>
            <p class="text-white-50 mb-0">Masukkan kode verifikasi untuk melanjutkan</p>
        </div>
        <div class="card-body p-4">
            @if(session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif
            
            <p class="text-center text-muted mb-4">
                Kode verifikasi telah dikirim ke channel terdaftar
            </p>
            
            <form id="2faChallengeForm" action="{{ route('2fa.challenge.verify') }}" method="POST">
                @csrf
                <div class="form-group">
                    <input type="text" class="form-control otp-input" id="code" name="code" placeholder="000000" maxlength="6" required autofocus>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-verify btn-block btn-lg text-white">
                        <i class="fas fa-check mr-2"></i>Verifikasi & Lanjutkan
                    </button>
                </div>
                
                <div class="text-center">
                    <button type="button" class="btn btn-link text-muted" id="resendBtn">
                        <i class="fas fa-redo mr-1"></i>Kirim Ulang Kode
                    </button>
                </div>
                
                <div class="text-center mt-3">
                    <small class="text-muted">
                        <i class="fas fa-clock mr-1"></i>
                        Kode akan kadaluarsa dalam <span id="countdown">5:00</span>
                    </small>
                </div>
            </form>
        </div>
        <div class="card-footer text-center p-3 bg-light">
            <small class="text-muted">
                <i class="fas fa-lock mr-1"></i>
                Koneksi aman • Two-Factor Authentication
            </small>
        </div>
    </div>
</div>

<script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('vendor/sweetalert2/dist/sweetalert2.min.js') }}"></script>
<script nonce="{{  csp_nonce() }}">
document.addEventListener("DOMContentLoaded", function (event) {
    let countdownTimer;
    let resendTimer;
    
    // Auto-focus and format OTP input
    $('#code').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
        if (this.value.length === 6) {
            $('#2faChallengeForm').submit();
        }
    });
    
    // Start countdown
    startCountdown(300); // 5 minutes in seconds
    
    // Verify form submission
    $('#2faChallengeForm').submit(function(e) {
        e.preventDefault();
        const code = $('#code').val();
        if (code.length !== 6) {
            Swal.fire({
                icon: 'warning',
                title: 'Peringatan!',
                text: 'Kode verifikasi harus 6 digit',
                confirmButtonColor: '#667eea'
            });
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
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: response.message,
                    confirmButtonColor: '#667eea'
                }).then(() => {
                    window.location.href = response.redirect;
                });
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: response.message || 'Verifikasi gagal',
                    confirmButtonColor: '#667eea'
                });
                $('#code').val('').focus();
            },
            complete: function() {
                btn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Resend OTP
    $('#resendBtn').click(function() {
        const btn = $(this);
        const originalText = btn.html();
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Mengirim...');
        
        $.ajax({
            url: '{{ route("2fa.resend") }}',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: response.message,
                    confirmButtonColor: '#667eea'
                });
                startCountdown(300); // Reset countdown
                startResendCountdown(30); // 30 seconds cooldown
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: response.message || 'Gagal mengirim ulang',
                    confirmButtonColor: '#667eea'
                });
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
            $('#resendBtn').html('<i class="fas fa-redo mr-1"></i>Kirim Ulang (' + timeLeft + ')');
            
            if (timeLeft <= 0) {
                clearInterval(resendTimer);
                $('#resendBtn').prop('disabled', false).html('<i class="fas fa-redo mr-1"></i>Kirim Ulang Kode');
            }
            timeLeft--;
        }, 1000);
    }
});
</script>
</body>
</html>