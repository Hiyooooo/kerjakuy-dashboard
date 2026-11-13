# ERD KerjaKuy Admin Panel

ERD lengkap dan DBML final disimpan di D:\Coding\Akarindo\10 Website\admin-panel\output-pembahasan\database-final-kerjakuy.dbml Dokumen ini merangkum relasi serta aturan penting saat membuat migration Laravel.

## Kelompok data

| Area              | Tabel inti                                                                                                               |
| ----------------- | ------------------------------------------------------------------------------------------------------------------------ |
| Akses             | `users`, `roles`, `permissions`, `user_roles`, `role_permissions`                                                        |
| Organisasi        | `offices`, `positions`, `job_desks`                                                                                      |
| Pegawai           | `employees`, identitas, profil pajak, keluarga, pendidikan, kepegawaian, karir, pelatihan, pembinaan, dan riwayat status |
| Jadwal & absensi  | shift, template jadwal, penugasan jadwal, jadwal harian, perangkat, absensi, lokasi, dan face recognition                |
| Permohonan        | kuota cuti, penyesuaian saldo, cuti, terlambat, lembur, dan tukar shift                                                  |
| Master kompensasi | BPJS, tunjangan, potongan, PTKP, PKP, dan bracket pajak                                                                  |
| Konten            | e-learning, pertanyaan, penugasan, jawaban, e-library, dan lampiran                                                      |

## Relasi penting

- Satu `employee` dapat memiliki satu akun `user`; akun admin nonpegawai tidak wajib terhubung ke pegawai.
- Satu `employee` memiliki satu `employee_identity_details`, banyak riwayat pajak, keluarga, pendidikan, kepegawaian, karir, pelatihan, pembinaan, dan status.
- Pegawai menggunakan `employee_work_schedule_assignments` untuk jadwal default. `employee_shift_schedules` dipakai untuk override pada tanggal tertentu atau hasil tukar shift.
- Satu pegawai hanya dapat memiliki satu jadwal harian pada tanggal yang sama dan satu perangkat aktif yang terkunci.
- `attendance_records` menyimpan snapshot jadwal, lokasi, metode, perangkat, menit keterlambatan, jarak, serta selfie masuk/pulang.
- `face_recognition_logs` selalu memiliki pegawai, tetapi dapat belum memiliki `attendance_record` jika verifikasi gagal sebelum absensi dibuat.
- `requests` menjadi induk permohonan cuti, terlambat, dan lembur. Keputusan satu approver disimpan langsung pada tabel ini.
- Sisa cuti dihitung dari kuota + carry over + penyesuaian − cuti yang disetujui.

## Aturan implementasi

- Jadikan ERD Notion sebagai sumber struktur tabel dan relasi lengkap.
- Buat migration per tahap fitur, bukan sekaligus.
- Jangan mengubah migration yang telah dijalankan. Perubahan struktur dibuat melalui migration baru.
- Tambahkan foreign key dan unique index yang tercantum pada ERD sejak migration awal tabel terkait dibuat.
- NIK, NPWP, foto referensi wajah, dan selfie absensi disimpan sebagai data sensitif dengan akses terbatas serta penyimpanan berkas privat.

## Catatan scope

ERD ini tidak memuat transaksi payroll, slip gaji, atau pembayaran gaji. Tabel BPJS, tunjangan, potongan, serta pajak hanya berfungsi sebagai master aturan.
