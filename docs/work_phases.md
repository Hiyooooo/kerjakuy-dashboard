## Alur migration per tahap

> Database memakai PostgreSQL. Pembagian Tahap 1 dan Tahap 2 berikut adalah kelompok domain; migration tetap dibuat kecil, hanya saat tabel benar-benar diperlukan oleh fitur aktif.

| Tahap | Cakupan migration                                                                                                                        | Belum dibuat pada tahap ini                              |
| ----- | ---------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------- |
| 0     | Migration bawaan Laravel dan konfigurasi PostgreSQL                                                                                      | Tabel modul HRIS                                         |
| 1     | `roles`, `users`                                                                                                                         | Data pegawai dan modul operasional                       |
| 2     | Master organisasi serta absensi: kantor, jabatan, job desk, waktu absen, cuti, hari libur, metode, lokasi, dan kebijakan toleransi wajah | Data pegawai, absensi transaksi, dan permohonan          |
| 3     | Pegawai inti: identitas, periode kepegawaian, penempatan jabatan, dan profil wajah                                                       | Jadwal, absensi, permohonan, serta data HRD lanjutan     |
| 4     | Jadwal, perangkat, absensi, dan log wajah                                                                                                | Permohonan, data HRD lanjutan, BPJS, pajak, pembelajaran |
| 5     | `requests`, detail permohonan, rincian hari cuti, kuota, dan penyesuaian cuti                                                            | BPJS, pajak, e-learning, e-library                       |
| 6     | Data HRD lanjutan, profil pajak, PTKP, PKP, BPJS, dan komponen tunjangan/potongan                                                        | E-learning dan e-library                                 |
| 7     | E-learning berversi dan e-library beserta lampiran                                                                                       | Tidak ada modul inti lain                                |
| 8     | Tidak ada tabel operasional baru; laporan memakai query, filter, dan index tambahan                                                      | —                                                        |

## Checklist wajib di setiap tahap

- [x] Tinjau tabel dan relasi ERD yang dipakai tahap ini.
- [ ] Bahas celah desain atau perubahan kebutuhan sebelum membuat migration.
- [ ] Buat hanya migration untuk tabel atau kolom tahap aktif.
- [ ] Tambahkan foreign key, unique index, check constraint, dan constraint PostgreSQL yang memang diperlukan tahap ini.
- [ ] Buat model, seeder, factory, dan validasi yang memakai tabel tersebut.
- [ ] Jalankan migration pada database kosong.
- [ ] Uji alur fitur tahap ini dengan data contoh.
- [ ] Catat perubahan ERD sebelum tahap ditutup.
- [ ] Jika ditemukan perbaikan setelah migration berjalan, buat migration baru; jangan mengubah migration lama.

<aside>
🧭

Urutan ini adalah rencana implementasi admin panel KerjaKuy. Tahap 1–5 membangun operasi HRIS inti; Tahap 6–7 melengkapi data HRD, kepatuhan, dan konten. Selesaikan kriteria tiap tahap sebelum berpindah.

</aside>

## Aturan pengerjaan

- [ ] Kerjakan tahap secara berurutan.
- [ ] Buat migration, seed data, dan uji fitur hanya untuk tabel yang dibutuhkan pada tahap aktif sebelum menutup tahap.
- [ ] Jangan membuat seluruh tabel ERD sekaligus.
- [ ] Gunakan PostgreSQL serta constraint database untuk aturan integritas yang tidak cukup dijaga validasi aplikasi.
- [ ] Pastikan setiap halaman admin memiliki validasi, hak akses, dan state kosong.
- [ ] Jangan membuat aplikasi karyawan atau payroll karena keduanya berada di luar scope saat ini.
- [ ] Gunakan ERD sebagai acuan relasi data dan Flowchart sebagai acuan alur admin.
- [ ] Tunda tabel pengaturan absensi sampai daftar setting yang dapat diubah disepakati; jangan gunakan pola key-value/EAV.

## Tahap 0 — Persiapan proyek

- [x] Siapkan aplikasi Laravel dan konfigurasi environment.
- [x] Pasang Laravel Boost sesuai instruksi proyek sebelum mengubah aplikasi.
- [x] Konfigurasi koneksi PostgreSQL bersama untuk admin panel dan API aplikasi karyawan.
- [x] Buat struktur layout admin panel, sidebar, topbar, dan halaman dashboard awal.
- [x] Siapkan pola route, controller, request validation, policy, dan pagination.

**Selesai jika:** admin dapat membuka aplikasi Laravel lokal, melihat layout admin panel, dan koneksi PostgreSQL berhasil.

## Tahap 1 — Login dan akses pengguna

- [x] Buat login, logout, dan proteksi route admin.
- [x] Buat manajemen pengguna.
- [x] Tentukan role tetap: Super Admin, HRD Admin, dan Security/Attendance Admin.
- [x] Hubungkan setiap pengguna ke tepat satu role.
- [x] Batasi menu serta aksi berdasarkan role melalui middleware atau Gate.
- [x] Siapkan akun admin awal melalui seeder.
- [x] Jangan membuat CRUD permission, `user_roles`, atau `role_permissions`.

Untuk instalasi baru, isi `KERJAKUY_ADMIN_EMAIL` dan `KERJAKUY_ADMIN_PASSWORD`, lalu jalankan `php artisan migrate --seed`. Hapus kedua nilai dari environment setelah akun dibuat. Seeder mempertahankan kata sandi akun yang sudah ada.

**Selesai jika:** pengguna tanpa role yang sesuai tidak dapat membuka menu atau aksi admin yang tidak diizinkan.

## Tahap 2 — Master organisasi dan absensi

- [x] Buat master jabatan.
- [x] Buat master kantor.
- [x] Buat job desk dan spesifikasi jabatan.
- [x] Buat master waktu absen.
- [ ] Buat kategori cuti.
- [ ] Buat hari libur global dan hari libur per kantor.
- [ ] Buat alasan di luar lokasi.
- [ ] Buat metode absen.
- [ ] Buat kebijakan toleransi wajah dengan periode berlaku.
- [ ] Buat lokasi absen dan radius lokasi.
- [ ] Tunda setting absensi sampai detail field pengaturannya disepakati.

**Selesai jika:** semua referensi utama tersedia untuk dipakai pada data pegawai, jadwal, absensi, dan permohonan.

## Tahap 3 — Pegawai inti

- [ ] Buat data pegawai inti tanpa menyimpan jabatan, kantor, atau status aktif secara ganda pada tabel pegawai.
- [ ] Buat identitas pegawai, termasuk NIK terenkripsi dan hash unik untuk validasi.
- [ ] Buat riwayat periode kepegawaian: jenis hubungan kerja, status, serta tanggal berlaku.
- [ ] Buat riwayat penempatan pegawai: kantor, jabatan, dan tanggal berlaku.
- [ ] Buat profil referensi wajah pegawai.
- [ ] Terapkan satu profil wajah aktif per pegawai.
- [ ] Buat halaman data pegawai nonaktif dari riwayat periode kepegawaian.
- [ ] Hubungkan akun pengguna ke pegawai bila diperlukan.

**Selesai jika:** admin dapat melihat pegawai beserta status, kantor, dan jabatan aktif dari riwayat yang tidak tumpang tindih.

## Tahap 4 — Jadwal, absensi, perangkat, dan keamanan

- [ ] Buat master shift kerja dari waktu absen.
- [ ] Buat template jadwal mingguan untuk hari kerja dan hari libur mingguan.
- [ ] Buat assignment template jadwal default kepada pegawai menurut periode berlaku.
- [ ] Buat override jadwal harian untuk shift khusus.
- [ ] Terapkan larangan periode jadwal aktif yang bertumpuk untuk pegawai yang sama.
- [ ] Buat registrasi perangkat.
- [ ] Simpan riwayat penguncian dan pelepasan perangkat.
- [ ] Terapkan aturan satu perangkat terkunci aktif per pegawai dan satu pegawai per perangkat aktif.
- [ ] Buat pencatatan absensi masuk dan pulang dengan snapshot jam jadwal efektif.
- [ ] Simpan metode, lokasi, perangkat, jarak, keterlambatan, foto selfie, dan alasan di luar lokasi pada absensi.
- [ ] Buat log verifikasi face recognition, termasuk percobaan gagal sebelum absensi tercipta.
- [ ] Siapkan endpoint API absensi untuk aplikasi karyawan di masa depan.

**Selesai jika:** data absensi dapat ditelusuri kembali ke pegawai, shift efektif, lokasi, perangkat, serta verifikasi wajah.

## Tahap 5 — Permohonan dan approval

- [ ] Buat satu header `requests` untuk cuti, terlambat, lembur, dan tukar shift.
- [ ] Buat permohonan cuti beserta rincian hari cuti dan entitlement yang dipakai.
- [ ] Hitung saldo cuti dari jatah + carry over + penyesuaian − cuti disetujui.
- [ ] Buat permohonan terlambat.
- [ ] Buat permohonan lembur.
- [ ] Buat permohonan tukar shift; saat disetujui, buat override jadwal bagi kedua pegawai.
- [ ] Buat daftar approval admin.
- [ ] Buat aksi setujui, tolak, dan batalkan permohonan.
- [ ] Simpan satu approver, waktu keputusan, serta catatan keputusan.
- [ ] Terapkan bahwa detail permohonan wajib sesuai jenis request.

**Selesai jika:** seluruh permohonan memiliki status dan satu approver yang jelas, tanpa struktur approval yang terduplikasi.

## Tahap 6 — HRD lanjutan, BPJS, dan pajak

- [ ] Buat data keluarga pegawai sebagai data pendukung tanggungan pajak.
- [ ] Buat data pendidikan, pelatihan, dan pembinaan pegawai.
- [ ] Buat profil pajak pegawai menurut periode berlaku.
- [ ] Simpan NPWP dan nomor kepesertaan sensitif dalam bentuk terenkripsi dengan hash unik terpisah.
- [ ] Buat master PTKP dan aturan PKP beserta lapisan tarif.
- [ ] Masukkan aturan PKP tahun 2024 sebagai data tahun pajak.
- [ ] Buat master program BPJS.
- [ ] Buat aturan kontribusi BPJS per program, pihak pembayar, dan periode berlaku.
- [ ] Buat kepesertaan BPJS pegawai.
- [ ] Buat satu master komponen kompensasi dengan tipe tunjangan atau potongan.
- [ ] Jangan membuat proses payroll, slip gaji, atau pembayaran gaji.

**Selesai jika:** admin dapat mengelola data HRD lanjutan, kepatuhan pajak/BPJS, dan master kompensasi tanpa membuat payroll.

## Tahap 7 — E-learning dan e-library

- [ ] Buat kategori e-learning.
- [ ] Buat identitas modul dan versi modul e-learning.
- [ ] Buat pertanyaan berdasarkan versi modul.
- [ ] Buat pilihan jawaban dan jawaban benar.
- [ ] Buat penugasan versi materi kepada pegawai, termasuk percobaan ulang bila diperlukan.
- [ ] Simpan progres, jawaban, nilai, dan penilaian manual untuk pertanyaan teks bila digunakan.
- [ ] Pastikan nilai lama tetap menunjuk materi serta soal versi lama.
- [ ] Buat kategori e-library.
- [ ] Buat data e-library: artikel, dokumen, tautan, atau video.
- [ ] Buat banyak lampiran file beserta urutan tampilnya.
- [ ] Buat status draf, terbit, dan arsip pada konten.

**Selesai jika:** admin dapat mengelola materi dan konten yang aman secara historis untuk diakses aplikasi karyawan kelak.

## Tahap 8 — Dashboard dan laporan SDM

- [ ] Isi dashboard dengan ringkasan pegawai, absensi, permohonan menunggu approval, dan jadwal hari ini.
- [ ] Buat laporan absensi.
- [ ] Buat laporan lokasi absensi.
- [ ] Buat laporan face recognition.
- [ ] Tambahkan filter tanggal, kantor, pegawai, dan status bila relevan.
- [ ] Tambahkan index query berdasarkan kebutuhan laporan yang telah diukur.
- [ ] Tambahkan ekspor laporan jika memang diminta client.
- [ ] Uji konsistensi angka dashboard terhadap data operasional.

**Selesai jika:** dashboard dan laporan membaca data nyata dari modul yang sudah dibuat.

## Penutupan proyek

- [ ] Uji seluruh role dan pembatasan akses.
- [ ] Uji semua validasi form.
- [ ] Uji skenario data kosong, data tidak valid, dan akses tanpa izin.
- [ ] Uji alur jadwal, absensi, tukar shift, permohonan, dan approval.
- [ ] Uji endpoint API yang diperlukan aplikasi karyawan.
- [ ] Jalankan migration dan seeder dari database PostgreSQL kosong.
- [ ] Dokumentasikan akun awal, konfigurasi environment, serta cara menjalankan proyek.
