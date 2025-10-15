<!-- Card Status Keamanan -->
<div class="card">
    <div class="card-header bg-success text-white">
        <h3 class="card-title">
            <i class="fas fa-shield-alt mr-2"></i>
            Keamanan Maksimal
        </h3>
    </div>
    <div class="card-body">
        <div class="text-center mb-3">
            <i class="fas fa-shield-check fa-3x text-success"></i>
            <h5 class="mt-2 text-success">Akun Terlindungi</h5>
        </div>
        
        <h6><strong>Keunggulan yang Aktif:</strong></h6>
        <ul class="text-sm list-unstyled">
            <li class="mb-2">
                <i class="fas fa-check text-success mr-2"></i>
                <strong>Password Otentikasi</strong>
                <small class="d-block text-muted ml-4">Login dengan password</small>
            </li>
            <li class="mb-2">
                <i class="fas fa-check text-success mr-2"></i>
                <strong>OTP Otentikasi</strong>
                <small class="d-block text-muted ml-4">Login tanpa password</small>
            </li>
            <li class="mb-2">
                <i class="fas fa-check text-success mr-2"></i>
                <strong>Two-Factor Otentikasi</strong>
                <small class="d-block text-muted ml-4">Verifikasi tambahan</small>
            </li>
            <li class="mb-2">
                <i class="fas fa-check text-success mr-2"></i>
                <strong>Multi-Channel Support</strong>
                <small class="d-block text-muted ml-4">Email & Telegram</small>
            </li>
        </ul>

        <div class="alert alert-info mt-3">
            <small>
                <i class="fas fa-lightbulb mr-1"></i>
                <strong>Tips:</strong> Simpan identifier Anda dengan aman untuk recovery!
            </small>
        </div>
    </div>
</div>

<!-- Card Statistik Keamanan -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-chart-line mr-2"></i>
            Statistik Keamanan
        </h3>
    </div>
    <div class="card-body">
        <div class="row text-center">
            <div class="col-6">
                <div class="border-right">
                    <h4 class="text-success">100%</h4>
                    <small class="text-muted">Keamanan Aktif</small>
                </div>
            </div>
            <div class="col-6">
                <h4 class="text-primary">{{ count($twoFactorStatus['channel']) }}</h4>
                <small class="text-muted">Channel Aktif</small>
            </div>
        </div>
    </div>
</div>