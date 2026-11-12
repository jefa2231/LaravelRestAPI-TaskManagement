# Task Management API (Laravel 12)

Project REST API sederhana untuk tugas Pemrograman Kerangka Kerja. Tidak menggunakan database; data tugas disimpan pada `storage/app/tasks.json`.

## Persyaratan
- PHP 8.2 atau lebih baru
- Composer 2
- Node.js 20 atau lebih baru dan npm

## Instalasi pertama kali

```bash
composer install
```

Salin `.env.example` menjadi `.env` (jika belum otomatis dibuat), lalu:

```bash
php artisan key:generate
php artisan optimize:clear
npm install
```

## Menjalankan aplikasi

Terminal 1:
```bash
php artisan serve --no-reload
```

Terminal 2:
```bash
npm run dev
```

Buka `http://127.0.0.1:8000`. **Jangan buka port Vite (5173)** - port itu hanya melayani asset dan menampilkan halaman informasi Vite.

Flag `--no-reload` diperlukan di Windows: tanpa flag itu Laravel menghapus sebagian environment variable sebelum menjalankan server, dan PHP gagal bind ke port (`Failed to listen on 127.0.0.1:8000`).

Alternatif: setelah dependensi Composer dan NPM terpasang, jalankan `composer run dev` untuk menjalankan server Laravel dan Vite bersamaan. Script tersebut sudah memakai `--no-reload`.

## Token demo
`rahasia_tugas_api_2026`

Masukkan token tersebut pada kolom autentikasi di halaman.

## Endpoint
Lihat `KONTRAK-API.md`. Ringkasnya:

| Method | Endpoint | Fungsi |
|---|---|---|
| GET | `/api/v1/tasks` | Daftar tugas, mendukung `page`, `per_page`, `status`, `q` |
| POST | `/api/v1/tasks` | Tambah tugas, membalas `201` dan header `Location` |
| GET | `/api/v1/tasks/{id}` | Detail tugas |
| PUT/PATCH | `/api/v1/tasks/{id}` | Ubah tugas |
| DELETE | `/api/v1/tasks/{id}` | Hapus tugas, membalas `204` |
| GET | `/api/v1/external/posts` | Proxy ke API eksternal |

Semua endpoint memakai `Authorization: Bearer {token}` dan `Accept: application/json`.

## Struktur kode
- `app/Support/TaskRepository.php` - baca/tulis JSON dengan `flock` dan penulisan atomik.
- `app/Enums/TaskStatus.php` - nilai status dan label bahasa Indonesia.
- `app/Http/Requests/` - validasi daftar, tambah, dan ubah tugas.
- `app/Http/Resources/TaskResource.php` - bentuk respons `data` termasuk `status_label`.
- `app/Http/Controllers/Api/V1/TaskController.php` - controller tipis di atas repository.
- `app/Http/Controllers/Api/V1/ExternalPostController.php` - panggilan API eksternal dengan timeout, retry, dan `502`.
- `routes/api.php` - prefix `v1`, rate limit, token, dan 404 JSON untuk endpoint tak dikenal.

## Pengujian

```bash
php artisan test
```

18 feature test mencakup autentikasi, pagination, filter status, pencarian, validasi `422`, `404`, `201` + `Location`, `204`, penyimpanan rusak, serta kegagalan API eksternal. Test memakai `storage/framework/testing/tasks.json`, sehingga `storage/app/tasks.json` tidak berubah.

## Catatan
- Tidak perlu menjalankan migration.
- Tidak perlu membuat database.
- Jangan menghapus `storage/app/tasks.json` jika ingin mempertahankan data tugas.
- Respons error API selalu bersih: tanpa `trace` atau path server meski `APP_DEBUG=true`.
- Untuk deployment, atur HTTPS, ganti token demo, isi `CORS_ALLOWED_ORIGINS` dengan origin frontend, dan set `APP_DEBUG=false`.
