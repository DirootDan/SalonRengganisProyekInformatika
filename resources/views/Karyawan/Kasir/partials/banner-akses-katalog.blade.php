<div class="info-proteksi-katalog">
    <div class="info-proteksi-ikon">🔐</div>
    <div class="info-proteksi-teks">
        <strong>Proteksi Hak Akses Satu Kali Pakai (One-Time Action)</strong>
        <p>Penambahan, perubahan, atau penghapusan katalog wajib melalui persetujuan Manajer disertai alasan wajib. Hak akses berlaku untuk <strong>1x aksi</strong> (otomatis kedaluwarsa dalam 60 menit atau saat logout) dan langsung terkunci kembali begitu perubahan disimpan atau dibatalkan.</p>
    </div>
</div>

@if ($penolakanTerbaru->isNotEmpty())
    <div class="daftar-penolakan-katalog" role="alert">
        @foreach ($penolakanTerbaru as $tolak)
            <div class="kartu-penolakan-katalog">
                <div>
                    <span class="badge-akses badge-akses-tolak">❌ Request Ditolak Manajer</span>
                    <strong>{{ $tolak->label_target }}</strong>
                    <small class="waktu-penolakan-katalog">(#{{ $tolak->kode_referensi }} &bull; {{ $tolak->diputuskan_pada?->format('H:i') }} WIB)</small>
                    <p class="teks-catatan-penolakan">
                        Alasan Manajer ({{ $tolak->manajer?->nama_staff ?? 'Manajer' }}):
                        <em>"{{ $tolak->catatan_manajer ?: 'Pengajuan perubahan katalog tidak disetujui.' }}"</em>
                    </p>
                </div>
            </div>
        @endforeach
    </div>
@endif
