<!-- 2FA Verification Form -->
<div id="2faVerificationSection" class="d-none">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h3 class="card-title">
                <i class="fas fa-check-circle mr-2"></i>
                Verifikasi 2FA
            </h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" id="cancel2faVerification">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <h6><i class="fas fa-info-circle mr-2"></i> Verifikasi Kode</h6>
                <p class="mb-0">Kode verifikasi 2FA telah dikirim ke channel yang Anda pilih. Masukkan kode 6 digit untuk menyelesaikan aktivasi 2FA.</p>
            </div>

            <form id="2faVerifyForm" method="POST">
                @csrf
                <div class="form-group">
                    <label for="2faCode">Kode 2FA:</label>
                    <input type="text" class="form-control text-center otp-input" id="2faCode" name="code" maxlength="6" placeholder="______" required>
                    <div class="mt-2">
                        <small class="text-muted">Kode akan kedaluwarsa dalam <span id="2faCountdown">5:00</span></small>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check mr-2"></i>Verifikasi 2FA
                </button>

                <button type="button" class="btn btn-secondary" id="resend2faBtn">
                    <i class="fas fa-redo mr-2"></i>Kirim Ulang (<span id="resendCountdown2fa">{{ $otpConfig['resend_seconds'] ?? 30 }}</span>)
                </button>
            </form>
        </div>
    </div>
</div>