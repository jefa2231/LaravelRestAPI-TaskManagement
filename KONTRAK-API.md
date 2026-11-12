# KONTRAK API - TASK MANAGEMENT

## Informasi
- Framework: Laravel 12
- Versi API: v1
- Base URL: `http://127.0.0.1:8000/api/v1`
- Format: JSON
- Penyimpanan: `storage/app/tasks.json` (tanpa database)

## Autentikasi
Semua endpoint API membutuhkan:
- `Accept: application/json`
- `Authorization: Bearer {token}`
- `Content-Type: application/json` untuk request body

Token demo di `.env`: `rahasia_tugas_api_2026`.
Nilai token dibaca dari `API_TOKEN`; ganti sebelum deployment.

## Endpoint
| Method | Endpoint | Fungsi | Sukses |
|---|---|---|---|
| GET | `/tasks` | Daftar tugas | 200 |
| GET | `/tasks/{id}` | Detail tugas | 200 |
| POST | `/tasks` | Tambah tugas | 201 |
| PUT/PATCH | `/tasks/{id}` | Ubah tugas | 200 |
| DELETE | `/tasks/{id}` | Hapus tugas | 204 |
| GET | `/external/posts` | Ambil data API eksternal | 200 |

Respons `POST /tasks` menyertakan header `Location: /api/v1/tasks/{id}`.

## Struktur Task
- `id`: string UUID
- `title`: string, wajib, maksimal 150 karakter
- `description`: string atau null, maksimal 1000 karakter
- `status`: `pending`, `in_progress`, atau `completed` (default `pending`)
- `status_label`: label bahasa Indonesia (`Menunggu`, `Dikerjakan`, `Selesai`)
- `due_date`: tanggal ISO 8601 atau null
- `created_at`: tanggal ISO 8601
- `updated_at`: tanggal ISO 8601

`status_label` dihitung server dari `App\Enums\TaskStatus`, sehingga client tidak perlu menyalin pemetaan status.

## Contoh tambah tugas
`POST /tasks`
```json
{
  "title": "Belajar Laravel",
  "description": "Membuat REST API",
  "status": "pending",
  "due_date": "2026-11-15"
}
```

Respons `201`:
```json
{
  "data": {
    "id": "9c9e0f3a-5e2f-4f6b-9d1a-0c2b7e8f4a11",
    "title": "Belajar Laravel",
    "description": "Membuat REST API",
    "status": "pending",
    "status_label": "Menunggu",
    "due_date": "2026-11-15T00:00:00+00:00",
    "created_at": "2026-11-12T08:15:00+00:00",
    "updated_at": "2026-11-12T08:15:00+00:00"
  }
}
```

## Pagination dan pencarian
`GET /tasks?page=1&per_page=10`

- `page`: integer minimal 1
- `per_page`: integer 1 sampai 50 (default 10)
- `status`: filter opsional `pending`, `in_progress`, atau `completed`
- `q`: pencarian opsional pada `title` dan `description`, maksimal 100 karakter

Contoh: `GET /tasks?status=pending&q=laravel&page=1&per_page=5`

Respons daftar memakai `{ "data": [], "links": {}, "meta": {} }`:
```json
{
  "data": [],
  "links": {
    "first": ".../tasks?page=1",
    "last": ".../tasks?page=2",
    "prev": null,
    "next": ".../tasks?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 2,
    "per_page": 10,
    "to": 10,
    "total": 14
  }
}
```

## Format respons
Sukses satu data:
```json
{ "data": {} }
```

Error validasi:
```json
{
  "message": "Judul tugas wajib diisi. (dan 1 error lainnya)",
  "errors": {
    "title": ["Judul tugas wajib diisi."]
  }
}
```

Error lain:
```json
{ "message": "Pesan kesalahan" }
```

Respons error API tidak menyertakan `exception`, `file`, atau `trace`, termasuk saat `APP_DEBUG=true`.

## HTTP Status
- 200: berhasil
- 201: data dibuat
- 204: data dihapus
- 401: token tidak valid/tidak ada
- 404: endpoint atau data tidak ditemukan
- 422: validasi gagal
- 429: rate limit terlampaui
- 500: penyimpanan JSON rusak atau tidak dapat ditulis
- 502: API eksternal gagal

## Keamanan dan integrasi
- Bearer Token melalui middleware `api.token`.
- Rate limit 60 request per menit per client.
- HTTPS wajib digunakan saat deployment.
- API eksternal memakai `EXTERNAL_API_URL`, timeout `EXTERNAL_API_TIMEOUT` (default 5 detik), dan retry `EXTERNAL_API_RETRY` (default 2 kali).
- Origin CORS diatur lewat `CORS_ALLOWED_ORIGINS` (pisahkan dengan koma, `*` untuk semua).
- Penulisan `tasks.json` memakai `flock` dan file sementara, lalu `rename`, sehingga request bersamaan tidak saling menimpa.
- Jangan gunakan token demo untuk deployment publik.

## Pengujian
```bash
php artisan test
```

Test berjalan pada `storage/framework/testing/tasks.json`, bukan data asli.
