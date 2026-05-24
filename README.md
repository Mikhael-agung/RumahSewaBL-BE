Ini README updated bro, tinggal copy-paste replace yang lama:

```markdown
# 🏠 Rumah Sewa Biru Laut — Backend Documentation

> Dokumentasi Backend (BE) — Sprint 1 Minggu 10

---

## 📋 Informasi Project

| | |
|---|---|
| **Project** | Rumah Sewa Biru Laut |
| **Mata Kuliah** | Manajemen Proyek Teknologi Informasi |
| **Stack BE** | Laravel 13 + JWT Auth |
| **Stack FE** | Flutter Web |
| **Database** | MySQL (Hostinger) |
| **BE Live** | https://rumahsewabl-be-production.up.railway.app |
| **Status** | 🟢 Sprint 1 Minggu 10 — Payment API Ready |

---

## 🗄️ Database

- **Host**: `auth-db1417.hstgr.io`
- **Database**: `u271192176_rsbl_test`
- **Local Dev**: MySQL via Laragon (`127.0.0.1:3306`)

### Tabel yang tersedia

| Tabel | Keterangan |
|---|---|
| `roles` | Role user: administrator, manager, penyewa |
| `users` | Data login user |
| `tenants` | Profil lengkap penyewa |
| `buildings` | Data gedung |
| `rooms` | Data kamar |
| `rentals` | Data penyewaan |
| `payment_deadlines` | Deadline pembayaran bulanan |
| `payments` | Data pembayaran + bukti |
| `notifications` | Notifikasi dalam sistem |
| `activity_logs` | Log aktivitas (admin only) |

---

## ⚙️ Setup & Instalasi

### Requirements
- PHP >= 8.2
- Composer >= 2.x
- MySQL >= 8.0
- Laragon (local dev)

### Langkah instalasi

```bash
# 1. Clone repository
git clone <repo-url>
cd rumah-sewa-biru-laut

# 2. Install dependencies
composer install

# 3. Copy env
cp .env.example .env

# 4. Generate app key
php artisan key:generate

# 5. Generate JWT secret
php artisan jwt:secret

# 6. Konfigurasi .env (lihat bagian ENV di bawah)

# 7. Storage link
php artisan storage:link

# 8. Jalankan server
php artisan serve
```

### Konfigurasi .env

```env
APP_NAME="Rumah Sewa Biru Laut"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=u271192176_rsbl_test
DB_USERNAME=root
DB_PASSWORD=

CACHE_STORE=file
SESSION_DRIVER=file
```

---

## 🔐 Autentikasi

Sistem auth menggunakan **JWT (JSON Web Token)** via package `tymon/jwt-auth`.

### Flow autentikasi

```
1. Flutter kirim POST /api/login (username + password)
2. Laravel validasi ke tabel users
3. Cocok → generate JWT token
4. Token dikirim ke Flutter
5. Flutter simpan token di local storage
6. Setiap request berikutnya Flutter kirim token di header:
   Authorization: Bearer <token>
7. Laravel decode token → valid → request diproses
8. Logout → token di-invalidate
```

---

## 📡 API Endpoints

Base URL Production: `https://rumahsewabl-be-production.up.railway.app/api`
Base URL Local: `http://127.0.0.1:8000/api`

### Public (tidak perlu token)

| Method | Endpoint | Deskripsi |
|---|---|---|
| GET | `/health` | Health check BE |
| POST | `/login` | Login user |

### Protected (perlu Bearer Token)

| Method | Endpoint | Role | Deskripsi |
|---|---|---|---|
| POST | `/logout` | Semua | Logout user |
| GET | `/me` | Semua | Data user yang login |
| POST | `/payments/upload` | penyewa | Upload bukti pembayaran |
| GET | `/payments/history` | penyewa | Riwayat pembayaran |
| GET | `/payments/pending` | manager, administrator | List pembayaran pending |
| POST | `/payments/{id}/verify` | manager, administrator | Verifikasi pembayaran |
| POST | `/payments/{id}/reject` | manager, administrator | Tolak pembayaran |

---

## 📨 Request & Response

### Login
```
POST /api/login
Content-Type: application/json

{
    "username": "admin01",
    "password": "admin123"
}
```
Response:
```json
{
    "success": true,
    "message": "Login berhasil",
    "token": "eyJ0eXAiOiJKV1Qi...",
    "user": {
        "id": 1,
        "username": "admin01",
        "role": "administrator"
    }
}
```

### Upload Bukti Pembayaran
```
POST /api/payments/upload
Authorization: Bearer <token>
Content-Type: multipart/form-data

payment_month  : 5          (integer, 1-12)
payment_year   : 2026       (integer)
amount         : 2000000    (numeric)
notes          : opsional   (string)
proof_file     : file       (jpg/jpeg/png/pdf, max 5MB)
```
Response:
```json
{
    "success": true,
    "message": "Bukti pembayaran berhasil diupload",
    "data": {
        "id": 5,
        "payment_code": "PAY-20260524-34AA3E",
        "rental_id": 1,
        "payment_month": 5,
        "payment_year": "2026",
        "amount": "2000000.00",
        "payment_status": "menunggu_verifikasi",
        ...
    }
}
```

### Verifikasi Pembayaran
```
POST /api/payments/{id}/verify
Authorization: Bearer <token_manager>
```
Response:
```json
{
    "success": true,
    "message": "Pembayaran berhasil diverifikasi",
    "data": { ... }
}
```

### Tolak Pembayaran
```
POST /api/payments/{id}/reject
Authorization: Bearer <token_manager>
Content-Type: application/json

{
    "rejection_reason": "Bukti pembayaran tidak jelas"
}
```
Response:
```json
{
    "success": true,
    "message": "Pembayaran berhasil ditolak",
    "data": { ... }
}
```

---

## 📊 Sprint Progress

### Sprint 1 — Minggu 9 ✅ DONE

| Task | Status |
|---|---|
| Setup Laravel + koneksi DB | ✅ Done |
| Install & konfigurasi JWT | ✅ Done |
| CORS konfigurasi | ✅ Done |
| AuthController (login & logout) | ✅ Done |
| Middleware CheckRole | ✅ Done |
| Routes api.php | ✅ Done |
| Deploy BE ke Railway | ✅ Done |

### Sprint 1 — Minggu 10

| Task | Status |
|---|---|
| Models (Payment, Rental, Tenant, Room, Building) | ✅ Done |
| PaymentController — upload bukti | ✅ Done |
| PaymentController — verifikasi & tolak | ✅ Done |
| PaymentController — history & pending | ✅ Done |
| Validasi magic bytes PDF | ✅ Done |
| CRUD Gedung, Kamar, Penyewa, Penyewaan | 📋 Todo |

---

## 👥 Role & Akses

| Role | Akses |
|---|---|
| administrator | Semua fitur + activity log |
| manager | Verifikasi pembayaran, kelola gedung/kamar/penyewa |
| penyewa | Upload pembayaran, lihat riwayat |

---

## 📦 Packages

| Package | Kegunaan |
|---|---|
| `laravel/framework` 13.x | Framework utama |
| `tymon/jwt-auth` | Autentikasi JWT |

---

> Last updated: Sprint 1 Minggu 10 — Mei 2026
```