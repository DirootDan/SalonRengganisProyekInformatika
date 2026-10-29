# Panduan Desain Antarmuka — Salon Rengganis

Dokumen ini menjadi acuan token desain, tipografi, palet warna, komponen, dan aksesibilitas untuk seluruh modul aplikasi **Salon Rengganis** (Pelanggan, Manajer, Kasir, Stylist, dan Therapist).

## 1. Palet Warna (CSS Custom Properties / Token)

Gunakan variabel CSS berikut secara konsisten:

```css
:root {
    --warna-utama: #9d4edd;
    --warna-utama-gelap: #7b2cbf;
    --warna-aksen: #b56576;
    --warna-latar: #fdfbf9;
    --warna-permukaan: #ffffff;
    --warna-teks: #1f2937;
    --warna-teks-redup: #6b7280;
    --warna-garis: #e5e7eb;

    /* Status & Umpan Balik */
    --warna-sukses: #15803d;
    --warna-sukses-latar: #dcfce7;
    --warna-peringatan: #b45309;
    --warna-peringatan-latar: #fef3c7;
    --warna-bahaya: #dc2626;
    --warna-bahaya-gelap: #b91c1c;
    --warna-bahaya-latar: #fee2e2;
}
```

## 2. Tipografi & Spasi
- **Font Utama:** `'Plus Jakarta Sans', 'Inter', system-ui, -apple-system, sans-serif`
- **Radius Sudut:** `--radius-kartu: 12px`, `--radius-tombol: 8px`
- **Bayangan:** `--bayangan-halus: 0 4px 16px rgba(17, 24, 39, 0.06)`, `--bayangan-modal: 0 20px 40px rgba(17, 24, 39, 0.18)`

## 3. Ikonografi & Komponen
- Gunakan ikon **SVG Lucide** satu garis (`stroke-width="2"`) pada seluruh komponen baru (seperti `<x-confirm-modal>`). Jangan menggunakan karakter emoji pada komponen baru.
- **Modal Konfirmasi (`<x-confirm-modal>`):**
  - Menggunakan atribut `role="alertdialog"`, `aria-modal="true"`, `aria-labelledby`, dan `aria-describedby`.
  - Tombol **Batal** memakai gaya sekunder netral dan menerima fokus awal.
  - Tombol **Konfirmasi / Hapus** memakai warna merah destruktif (`--warna-bahaya`) dan otomatis nonaktif (`disabled`) ketika form sedang dikirim.
  - Responsif penuh pada layar seluler `375px`.
