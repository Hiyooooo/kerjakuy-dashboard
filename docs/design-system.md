# 02. Design System KerjaKuy

## 1. Identitas visual

| Sumbu           | Keputusan                                                                                    |
| --------------- | -------------------------------------------------------------------------------------------- |
| Suasana         | Friendly, playful, approachable, tetap jelas dan dewasa                                      |
| Mode            | Terang dengan bidang putih dan cream                                                         |
| Warna identitas | Warm Coral `#FB7185`, Sunny Amber `#F59E0B`                                                  |
| Font            | Manrope untuk judul; Nunito Sans untuk isi dan kontrol                                       |
| Bentuk          | Panel percakapan empuk dengan sudut besar, tetapi input dan chip lebih rapat                 |
| Komposisi       | Hero editorial dua kolom; demo Perizinan jadi fokus; modul memakai layout bervariasi         |
| Motif khas      | Bubble ekor pendek dan karakter “Kuy” abstrak pada UI, bukan maskot berulang di tiap section |
| Motion          | Halus, singkat, non-looping; state akhir statis untuk reduced-motion                         |

Kombinasi Manrope dan Nunito Sans memberi bentuk ramah tanpa memakai kombinasi tipografi di registri seri yang diberikan. Bubble menandai percakapan dan permintaan izin, sementara layout asimetris menghindari kesan kumpulan kartu seragam.

## 2. Token warna dan kontras

Semua rasio di bawah dihitung dengan rumus luminans relatif WCAG 2.1, pembulatan dua desimal. Ambang teks normal 4.5:1; teks besar dan batas komponen penting minimal 3:1.

| Token               | Nilai     | Pemakaian                                                |
| ------------------- | --------- | -------------------------------------------------------- |
| `--color-coral`     | `#FB7185` | Fill CTA, sorotan, dan bubble utama                      |
| `--color-amber`     | `#F59E0B` | Fill chip, penanda tanggal, aksen demo                   |
| `--color-ink`       | `#2B2423` | Teks utama dan teks di atas fill coral/amber             |
| `--color-muted`     | `#655B58` | Teks sekunder                                            |
| `--color-coral-ink` | `#BE123C` | Tautan/teks coral pada permukaan terang                  |
| `--color-amber-ink` | `#874400` | Tautan/teks amber pada permukaan terang                  |
| `--color-paper`     | `#FFF8F3` | Bidang cream                                             |
| `--color-white`     | `#FFFFFF` | Kartu dan ruang UI                                       |
| `--color-line`      | `#E9D9D2` | Pemisah dekoratif saja, bukan satu-satunya penanda input |
| `--color-control`   | `#8C5B52` | Garis kontrol dan fokus saat kontras diperlukan          |
| `--color-success`   | `#26734D` | Status berhasil dengan label teks                        |
| `--color-danger`    | `#B42318` | Status gagal dengan label teks                           |

```css
:root {
    --color-coral: #fb7185;
    --color-amber: #f59e0b;
    --color-ink: #2b2423; /* 14.48:1 pada paper; 15.22:1 pada white */
    --color-muted: #655b58; /* 6.26:1 pada paper; 6.58:1 pada white */
    --color-coral-ink: #be123c; /* 5.98:1 pada paper; 6.29:1 pada white */
    --color-amber-ink: #874400; /* 6.99:1 pada paper; 7.35:1 pada white */
    --color-paper: #fff8f3;
    --color-white: #ffffff;
    --color-line: #e9d9d2;
    --color-control: #8c5b52; /* 5.35:1 pada paper */
    --color-success: #26734d; /* 5.48:1 pada paper */
    --color-danger: #b42318; /* 6.25:1 pada paper */
}
```

Pasangan tombol utama tervalidasi: teks ink pada coral `5.66:1`, teks ink pada amber `7.09:1`. Teks putih pada coral hanya `2.69:1`, dan pada amber `2.15:1`; **dilarang memakai teks putih** pada kedua fill. Jangan gunakan coral atau amber asli sebagai teks kecil di atas permukaan terang; gunakan varian ink di atas. Border `--color-line` berkontras `1.30:1` pada paper dan hanya boleh menjadi dekorasi. Gunakan `--color-control` atau indikator bentuk/label untuk kontrol.

## 3. Tipografi

| Peran     | Font        | Desktop | Mobile | Weight / tracking / line-height |
| --------- | ----------- | ------: | -----: | ------------------------------- |
| H1        | Manrope     |   56 px |  40 px | 750; `-0.04em`; 1.03            |
| H2        | Manrope     |   40 px |  32 px | 700; `-0.03em`; 1.1             |
| H3        | Manrope     |   24 px |  21 px | 700; `-0.02em`; 1.2             |
| Isi utama | Nunito Sans |   18 px |  17 px | 500; `0`; 1.55                  |
| Isi/UI    | Nunito Sans |   16 px |  15 px | 500–650; `0`; 1.45              |
| Label     | Nunito Sans |   13 px |  13 px | 700; `0.01em`; 1.3              |
| Nominal   | Nunito Sans |   24 px |  22 px | 700; angka tabular              |

Gunakan sentence case. Hindari eyebrow kapital di atas setiap judul. Jangan jadikan teks kecil sebagai satu-satunya cara menyampaikan status.

## 4. Grid, jarak, dan bentuk

- Container maksimum 1160 px, margin auto, padding sisi 24 px desktop dan 18 px mobile.
- Gunakan skala jarak 4, 8, 12, 16, 24, 32, 48, 64, 88 px.
- Jarak antar-section 96–128 px desktop, 64–80 px mobile; variasikan sesuai kepadatan.
- Hero 56/44 dengan teks kiri dan komposisi mockup izin kanan. Di ponsel: headline, CTA, lalu mockup; jangan menyembunyikan demo.
- Modul: Perizinan satu panel lebar; Absensi dengan foto dan ringkasan UI; Gaji sebagai slip vertikal; Exam sebagai kuis; Library sebagai daftar hasil pencarian. Jangan pakai satu pola kartu lima kali.
- Radius: kontrol 10 px, chip 14 px, kartu 22 px, bubble utama 32 px. Gunakan radius penuh hanya untuk chip kecil dan avatar inisial.

## 5. Komponen

| Komponen        | Anatomi dan aturan                                                                                             |
| --------------- | -------------------------------------------------------------------------------------------------------------- |
| Navigasi        | Wordmark buatan sendiri, tautan modul/cara kerja/FAQ, tombol `Lihat simulasi`; menu ponsel keyboard accessible |
| Tombol primer   | Tinggi minimal 48 px, teks `--color-ink` di atas coral; fokus 3 px `--color-control` dengan offset             |
| Tombol sekunder | Latar white, outline `--color-control`, teks ink; hover perubahan fill ringan tanpa geser layout               |
| Bubble          | Tail sederhana pada maksimal satu panel utama per viewport; jangan menyelubungi seluruh konten                 |
| Kartu           | White/paper dengan variasi radius; bayangan tipis hanya untuk mengangkat demo dari latar                       |
| Form demo       | Label selalu terlihat, error dekat kolom, tombol aktif memiliki nama yang menjelaskan tindakan                 |
| Status          | Ikon Lucide plus label teks; jangan mengandalkan warna atau emoji saja                                         |
| Karakter Kuy    | Karakter kecil non-fotoreal buatan sendiri; gunakan seperlunya sebagai identitas, bukan pengganti foto konteks |

## 6. Motion dan aksesibilitas

- Hover/focus 140–180 ms; perubahan state demo maksimal 220 ms; easing `cubic-bezier(0.2, 0.7, 0.2, 1)`.
- Izinkan satu entrance sederhana pada hero dan satu reveal per section bila diperlukan untuk urutan baca.
- **Nol animasi looping**, parallax, scroll-linked, atau gerak dekorasi terus-menerus.
- Dengan `prefers-reduced-motion: reduce`, hapus transform/reveal dan tampilkan state akhir tanpa delay.
- Pastikan fokus terlihat, tab order mengikuti visual, ikon dekoratif `aria-hidden`, semua kontrol punya nama, target sentuh minimal 44×44 px, dan kontras minimum terpenuhi.
