## Aturan pengerjaan

- [ ] Kerjakan tahap secara berurutan.
- [ ] Buat migration, seed data, dan uji fitur hanya untuk tabel yang dibutuhkan pada tahap aktif sebelum menutup tahap.
- [ ] Pastikan setiap halaman admin memiliki validasi, hak akses, dan state kosong.
- [ ] Jangan membuat aplikasi karyawan atau payroll karena keduanya berada di luar scope saat ini.
- [ ] Gunakan ERD sebagai acuan relasi data dan Flowchart sebagai acuan alur admin.

## Tahap 0 — Persiapan proyek

- [x] Siapkan aplikasi Laravel dan konfigurasi environment.
- [x] Pasang Laravel Boost sesuai instruksi proyek sebelum mengubah aplikasi.
- [x] Konfigurasi database bersama untuk admin panel dan API aplikasi karyawan.
- [x] Buat struktur layout admin panel, sidebar, topbar, dan halaman dashboard awal.
- [x] Siapkan pola route, controller, request validation, policy, dan pagination.

**Selesai jika:** admin dapat membuka aplikasi Laravel lokal, melihat layout admin panel, dan koneksi database berhasil.

## Tahap 1 — Login dan akses pengguna

- [x] Buat login, logout, dan proteksi route admin.
- [ ] Buat manajemen pengguna.
- [ ] Buat manajemen role.
- [ ] Buat manajemen permission.
- [ ] Hubungkan pengguna dengan role dan permission.
- [ ] Batasi menu serta aksi berdasarkan permission.
- [ ] Siapkan akun admin awal melalui seeder.

**Selesai jika:** pengguna tanpa akses tidak dapat membuka menu atau aksi yang tidak diizinkan.

## Tahap 2 — Master organisasi dan absensi

- [ ] Buat master jabatan.
- [ ] Buat master kantor.
- [ ] Buat job desk dan spesifikasi jabatan.
- [ ] Buat master waktu absen.
- [ ] Buat kategori cuti.
- [ ] Buat hari libur.
- [ ] Buat alasan di luar lokasi.
- [ ] Buat metode absen.
- [ ] Buat setting absen.
- [ ] Buat setting toleransi wajah.
- [ ] Buat lokasi absen dan radius lokasi.

**Selesai jika:** semua referensi utama tersedia untuk dipakai pada data pegawai, jadwal, absensi, dan permohonan.

## Tahap 3 — HRD dan data pegawai

- [ ] Buat data pegawai.
- [ ] Buat data keluarga pegawai.
- [ ] Buat data pendidikan pegawai.
- [ ] Buat data kepegawaian.
- [ ] Buat data karir.
- [ ] Buat data pelatihan.
- [ ] Buat data pembinaan.
- [ ] Buat status pegawai aktif, nonaktif, dan resign.
- [ ] Buat halaman data pegawai nonaktif.
- [ ] Hubungkan pegawai dengan kantor, jabatan, dan akun pengguna bila diperlukan.

**Selesai jika:** admin dapat melihat profil pegawai beserta seluruh riwayat HRD tanpa duplikasi data.

## Tahap 4 — Jadwal, absensi, perangkat, dan keamanan

- [ ] Buat master shift kerja dari waktu absen.
- [ ] Buat penjadwalan shift pegawai per tanggal.
- [ ] Buat pengajuan dan persetujuan tukar shift.
- [ ] Terapkan aturan satu jadwal aktif per pegawai per tanggal.
- [ ] Buat registrasi perangkat.
- [ ] Terapkan aturan satu perangkat terkunci aktif per pegawai.
- [ ] Buat pencatatan absensi masuk dan pulang.
- [ ] Simpan metode, lokasi, perangkat, dan alasan di luar lokasi pada absensi.
- [ ] Buat log verifikasi face recognition.
- [ ] Siapkan endpoint API absensi untuk aplikasi karyawan di masa depan.

**Selesai jika:** data absensi dapat dicatat dan ditelusuri kembali ke pegawai, jadwal, lokasi, perangkat, dan verifikasi wajah.

## Tahap 5 — Permohonan dan approval

- [ ] Buat permohonan cuti.
- [ ] Buat permohonan terlambat.
- [ ] Buat permohonan lembur.
- [ ] Buat daftar approval admin.
- [ ] Buat aksi setujui, tolak, dan batalkan permohonan.
- [ ] Simpan satu approver, waktu keputusan, serta catatan keputusan.
- [ ] Hubungkan cuti dengan kategori cuti.
- [ ] Hubungkan keterlambatan dengan data absensi bila tersedia.
- [ ] Hubungkan lembur dengan jadwal shift bila tersedia.

**Selesai jika:** seluruh permohonan memiliki status yang jelas dan dapat ditinjau oleh satu admin/approver.

## Tahap 6 — Master kompensasi dan pajak

- [ ] Buat master BPJS perusahaan.
- [ ] Buat master BPJS karyawan.
- [ ] Buat master tunjangan.
- [ ] Buat master potongan.
- [ ] Buat master PTKP.
- [ ] Buat master PKP.
- [ ] Masukkan aturan PKP tahun 2024 melalui data tahun pajak.
- [ ] Tambahkan periode berlaku pada BPJS dan pajak.

**Selesai jika:** admin dapat mengelola seluruh aturan master kompensasi dan pajak tanpa membuat payroll.

## Tahap 7 — E-learning dan e-library

- [ ] Buat kategori e-learning.
- [ ] Buat modul atau materi e-learning.
- [ ] Buat pertanyaan e-learning.
- [ ] Buat pilihan jawaban dan jawaban benar.
- [ ] Buat penugasan materi kepada pegawai.
- [ ] Simpan progres, jawaban, dan nilai pembelajaran.
- [ ] Buat kategori e-library.
- [ ] Buat data e-library: artikel, dokumen, tautan, atau video.
- [ ] Buat status draf, terbit, dan arsip pada konten.

**Selesai jika:** admin dapat mengelola materi dan konten yang nanti dapat diakses melalui aplikasi karyawan.

## Tahap 8 — Dashboard dan laporan SDM

- [ ] Isi dashboard dengan ringkasan pegawai, absensi, permohonan menunggu approval, dan jadwal hari ini.
- [ ] Buat laporan absensi.
- [ ] Buat laporan lokasi absensi.
- [ ] Buat laporan face recognition.
- [ ] Tambahkan filter tanggal, kantor, pegawai, dan status bila relevan.
- [ ] Tambahkan ekspor laporan jika memang diminta client.
- [ ] Uji konsistensi angka dashboard terhadap data operasional.

**Selesai jika:** dashboard dan laporan membaca data nyata dari modul yang sudah dibuat.

## Penutupan proyek

- [ ] Uji seluruh role dan permission.
- [ ] Uji semua validasi form.
- [ ] Uji skenario data kosong, data tidak valid, dan akses tanpa izin.
- [ ] Uji alur jadwal, absensi, tukar shift, permohonan, dan approval.
- [ ] Uji endpoint API yang diperlukan aplikasi karyawan.
- [ ] Jalankan migration dan seeder dari database kosong.
- [ ] Dokumentasikan akun awal, konfigurasi environment, serta cara menjalankan proyek.
