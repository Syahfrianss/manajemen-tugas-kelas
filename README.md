# Aplikasi Manajemen Tugas Kelas

Aplikasi manajemen tugas kelas: guru membuat tugas, siswa melihat dan menandai selesai.

**Stack:** Laravel 12 (REST API + Sanctum), MySQL (Docker), frontend Blade + JavaScript.

## Cara menjalankan dari nol

Prasyarat: PHP 8.2+, Composer, Docker Desktop.

1. `git clone https://github.com/USERNAME/class-task-tracker.git && cd class-task-tracker`
2. `composer install`
3. `copy .env.example .env` (Windows) atau `cp .env.example .env`
4. `php artisan key:generate`
5. `docker compose up -d` (MySQL di port 3308; tunggu ±30 detik)
6. `php artisan migrate --seed`
7. `php artisan serve`, lalu buka http://127.0.0.1:8000

Password database `secret` hanya untuk pengembangan lokal (container Docker).

## Akun contoh (password: `password123`)

| Peran | Email |
|---|---|
| Guru | guru@test.com |
| Siswa (Kelas 10A) | budi@test.com |
| Siswa (Kelas 10B) | siti@test.com |

## Endpoint

| Method | Path | Akses |
|---|---|---|
| GET | /api/classes | publik |
| POST | /api/register | publik (throttle 10/menit) |
| POST | /api/login | publik (throttle 5/menit) |
| GET | /api/me | login |
| POST | /api/logout | login |
| GET | /api/tasks?search=&status=&page=&per_page= | login (siswa hanya tugas kelasnya) |
| POST | /api/tasks/{id}/complete | siswa |
| POST | /api/tasks | guru |
| PUT | /api/tasks/{id} | guru |
| DELETE | /api/tasks/{id} | guru |

Error selalu JSON: 401 (belum login), 403 (tidak punya akses), 404, 422 (validasi, berisi `errors`).

## Keputusan desain

- **Laravel + Sanctum:** hashing, validasi, migrasi, dan middleware tersedia bawaan.
- **Peran di kolom `users.role`**, dibatasi lewat middleware `role:teacher` / `role:student`. Otorisasi dicek di server, bukan hanya disembunyikan di UI.
- **Status selesai per siswa** lewat tabel `task_completions` dengan unique `(task_id, user_id)`, sehingga klik ganda tidak membuat duplikat.
- **Password** di-hash bcrypt (cast `hashed`), disembunyikan dari respons JSON (`$hidden`).
- **Anti-XSS:** semua data ditampilkan lewat fungsi `esc()`. **Anti-SQL Injection:** Eloquent memakai prepared statement.
- **Login:** pesan error generik dan rate limit untuk mencegah enumerasi akun dan brute force.
- Token disimpan di `localStorage` demi kesederhanaan (trade-off: rentan jika ada XSS).

## Belum selesai / akan diperbaiki

- Registrasi guru terbuka untuk siapa saja; seharusnya lewat admin atau kode undangan.
- Guru bisa mengubah/menghapus tugas guru lain (belum ada pengecekan kepemilikan).
- Filter status hanya berlaku untuk siswa.
- Tenggat tampil dalam UTC sehingga bisa selisih jam di form ubah.
- Tombol Ubah mengambil data dari daftar (maks. 50), belum ada endpoint detail.
- Belum ada tes otomatis, dokumentasi OpenAPI, dan Docker untuk aplikasi (Docker hanya untuk MySQL).
- Dikembangkan dengan MySQL 5.7 di Docker; [sebutkan jika kamu mengganti ke 8.0].

## Penggunaan AI

Saya memakai Claude (AI) sebagai pemandu:
- **Dibantu AI:** [contoh: rancangan tabel, kode AuthController/TaskController, middleware peran, frontend, draf README].
- **Saya kerjakan/ubah sendiri:** [contoh: setup Docker dan MySQL, debugging migrasi yang kosong, memperbaiki cast password yang hilang di User.php, pengujian endpoint dan otorisasi, menjalankan dan memverifikasi semuanya].

Saya sudah membaca dan memahami kode yang dikumpulkan.