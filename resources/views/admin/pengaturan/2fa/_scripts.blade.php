<script nonce="{{  csp_nonce() }}">
document.addEventListener("DOMContentLoaded", function (event) {
    // Toggle channel sections
    $('input[name="channel"]').change(function() {
        if ($(this).val() === 'email') {
            $('#emailSection').removeClass('d-none');
            $('#telegramSection').addClass('d-none');
            $('#emailIdentifier').attr('name', 'identifier');
            $('#telegramIdentifier').removeAttr('name');
            $('#telegramIdentifier').removeAttr('required');
            $('#emailIdentifier').attr('required', 'required');
        } else {
            $('#emailSection').addClass('d-none');
            $('#telegramSection').removeClass('d-none');
            $('#telegramIdentifier').attr('name', 'identifier');
            $('#emailIdentifier').removeAttr('name');
            $('#emailIdentifier').removeAttr('required');
            $('#telegramIdentifier').attr('required', 'required');
        }
    });

    $('input[name="channel"]').eq(0).trigger('change');     

    // Setup form submission
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
                    response?.message || 'Gagal mengirim kode aktivasi',
                    'error'
                );
            },
            complete: function() {
                btn.prop('disabled', false).html(originalText);
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

    // Enhanced UI interactions for 2FA active state
    $('.info-box').hover(
        function() {
            $(this).addClass('shadow');
        }, 
        function() {
            $(this).removeClass('shadow');
        }
    );

    // Tooltip untuk tombol disable
    $('#disable2faBtn').tooltip({
        title: 'Klik untuk menonaktifkan fitur 2FA',
        placement: 'top'
    });
});
</script>