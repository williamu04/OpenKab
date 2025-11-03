<!-- OTP Verification Form -->
<div id="otpVerificationSection" class="d-none">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h3 class="card-title">
                <i class="fas fa-check-circle mr-2"></i>
                Verifikasi OTP
            </h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" id="cancelOtpVerification">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <h6><i class="fas fa-info-circle mr-2"></i> Verifikasi Kode</h6>
                <p class="mb-0">Kode OTP telah dikirim ke channel yang Anda pilih. Masukkan kode 6 digit untuk menyelesaikan aktivasi OTP.</p>
            </div>
            
            <form id="otpVerifyForm" method="POST">
                @csrf
                <div class="form-group">
                    <label for="otpCode">Kode OTP:</label>
                    <input type="text" class="form-control otp-input" id="otpCode" name="otp" maxlength="6" placeholder="______" required>
                    <div class="mt-2">
                        <small class="text-muted">Kode akan kedaluwarsa dalam <span id="countdown">5:00</span></small>
                    </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check mr-2"></i>Verifikasi OTP
                </button>
                
                <button type="button" class="btn btn-secondary" id="resendOtpBtn">
                    <i class="fas fa-redo mr-2"></i>Kirim Ulang (<span id="resendCountdownOtp">{{ $otpConfig['resend_seconds'] ?? 30 }}</span>)
                </button>
            </form>
        </div>
    </div>
</div>