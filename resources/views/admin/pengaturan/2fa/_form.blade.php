<div class="form-group">
    <label for="channel"><strong>Pilih Channel Verifikasi:</strong></label>
    <div class="custom-control custom-radio">
        <input class="custom-control-input" type="radio" id="emailChannel" name="channel" value="email" {{ $channel ?? 'email' === 'email' ? 'checked' : '' }}>
        <label class="custom-control-label" for="emailChannel">
            <i class="fas fa-envelope mr-2"></i>Email
            <small class="d-block text-muted">Kode verifikasi akan dikirim ke alamat email</small>
        </label>
    </div>
    <div class="custom-control custom-radio">
        <input class="custom-control-input" type="radio" id="telegramChannel" name="channel" value="telegram" {{ $channel ?? 'email' === 'telegram' ? 'checked' : '' }}>
        <label class="custom-control-label" for="telegramChannel">
            <i class="fab fa-telegram mr-2"></i>Telegram
            <small class="d-block text-muted">Kode verifikasi akan dikirim ke bot Telegram</small>
        </label>
    </div>
</div>

<div class="form-group {{ $channel ?? 'email' === 'email' ? '' : 'd-none' }}" id="emailSection">
    <label for="emailIdentifier"><strong>Alamat Email:</strong></label>
    <input type="email" class="form-control" id="emailIdentifier" name="identifier" placeholder="nama@example.com" value="{{ $identifier ?? '' }}" required>
    <small class="form-text text-muted">Pastikan email dapat diakses untuk menerima kode verifikasi</small>
</div>

<div class="form-group {{ $channel ?? 'email' === 'telegram' ? '' : 'd-none' }}" id="telegramSection">
    <label for="telegramIdentifier"><strong>Telegram Chat ID:</strong></label>
    <input type="text" class="form-control" id="telegramIdentifier" name="identifier" placeholder="123456789" value="{{ $identifier ?? '' }}" required>
    <small class="form-text text-muted">
        Dapatkan Chat ID dengan mengirim pesan ke <a href="https://t.me/userinfobot" target="_blank">@userinfobot</a>
    </small>
    <small class="form-text text-muted">
        <i class="fas fa-info-circle mr-1"></i>
            Hubungi bot {{ env('TELEGRAM_BOT_NAME', 'belum diset') }} dan ketik /start agar bot telegram dapat mengirim kode OTP ke Anda
    </small>
</div>