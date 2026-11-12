# Konteks Proyek KerjaKuy Admin Panel

## Tujuan

KerjaKuy Admin Panel adalah dashboard internal untuk admin dan HR mengelola data HRIS. Aplikasi ini memakai Laravel, Blade, dan Tailwind CSS.

Database yang sama akan dipakai oleh API untuk aplikasi karyawan pada tahap terpisah. Pekerjaan saat ini hanya mencakup dashboard admin/HR.

## Scope

- Akses pengguna: akun, role, dan permission.
- Master: jabatan, kantor, job desk, waktu dan pengaturan absensi, kategori cuti, hari libur, alasan di luar lokasi, lokasi absensi, metode absensi, toleransi wajah, BPJS, tunjangan, potongan, dan pajak.
- HRD: data pegawai, keluarga, pendidikan, kepegawaian, karir, pelatihan, pembinaan, dan pegawai nonaktif.
- Operasional: jadwal kerja, tukar shift, perangkat terkunci, absensi, dan face recognition.
- Permohonan: cuti, terlambat, dan lembur dengan satu approver.
- Konten: e-learning dan e-library.
- Dashboard serta laporan SDM untuk absensi, lokasi, dan face recognition.

## Di luar scope

- Aplikasi karyawan.
- Payroll, slip gaji, dan pembayaran gaji.
- Dukungan multi-perusahaan.
- Approval bertingkat.

## Keputusan proyek

- Satu perusahaan dapat memiliki banyak kantor.
- Satu pegawai dapat memiliki satu akun pengguna; akun admin nonpegawai tidak harus terhubung ke pegawai.
- Satu pegawai hanya memiliki satu perangkat absensi yang terkunci aktif pada waktu yang sama.
- Pegawai memakai template jadwal kerja default. Jadwal harian digunakan untuk override dan tukar shift.
- Hari libur mingguan diatur melalui template jadwal kerja. Tabel hari libur menyimpan tanggal libur nasional atau khusus.
- Saldo cuti dihitung dari kuota, carry over, penyesuaian, dan cuti yang disetujui.
- Data sensitif seperti NIK, NPWP, foto referensi wajah, dan selfie absensi hanya dapat diakses oleh role yang berwenang dan disimpan pada penyimpanan privat.

## Aturan database dan migration

Jangan membuat seluruh tabel ERD sekaligus. Migration dibuat per tahap implementasi dan hanya untuk tabel yang diperlukan fitur pada tahap tersebut.

Jika ada perbaikan struktur setelah sebuah migration dijalankan, buat migration baru untuk menambah atau mengubah struktur. Jangan mengubah migration lama yang sudah pernah dipakai.

## Arah desain

Gunakan [design-system.md](./design-system.md) sebagai sumber visual. Admin panel mengadopsi identitas KerjaKuy: terang, friendly, playful, dan tetap matang untuk pekerjaan HR.

- Warna utama: coral dan amber dengan permukaan paper/white.
- Font: Manrope untuk judul dan Nunito Sans untuk isi serta kontrol.
- Gunakan teks gelap pada permukaan coral/amber; jangan gunakan teks putih di atas kedua warna tersebut.
- Terapkan status dengan teks dan bentuk selain warna saja.
- Fokus keyboard, target sentuh, dan `prefers-reduced-motion` wajib didukung.

## Referensi perencanaan

- Flowchart, ERD final, dan checklist tahapan pengerjaan disimpan di workspace Notion proyek.
- `docs/erd.md` akan memuat ringkasan dan tautan ERD final saat dibuat.
