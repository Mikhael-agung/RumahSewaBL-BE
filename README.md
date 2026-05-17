# 🏠 Rumah Sewa Biru Laut — Backend Documentation

> Dokumentasi sementara progress Backend (BE) — Sprint 1 Minggu 9

---

## 📋 Informasi Project

| | |
|---|---|
| **Project** | Rumah Sewa Biru Laut |
| **Mata Kuliah** | Manajemen Proyek Teknologi Informasi |
| **Stack BE** | Laravel 13 + JWT Auth |
| **Stack FE** | Flutter Web |
| **Database** | MySQL (Hostinger) |
| **Status** | 🟡 Sprint 1 — In Progress |

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

# 7. Jalankan server
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

### Guard

```php
// config/auth.php
'api' => [
    'driver' => 'jwt',
    'provider' => 'users',
]
```

---

## 📡 API Endpoints

Base URL: `http://127.0.0.1:8000/api`

### Public (tidak perlu token)

| Method | Endpoint | Deskripsi |
|---|---|---|
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

### Contoh request login

```json
POST /api/login
Headers: Accept: application/json
Content-Type: application/json

{
    "username": "admin01",
    "password": "admin123"
}
```

### Contoh response sukses

```json
{
    "success": true,
    "message": "Login berhasil",
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "user": {
        "id": 1,
        "username": "admin01",
        "role": "administrator"
    }
}
```

### Contoh request dengan token

```
GET /api/me
Headers:
  Accept: application/json
  Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

---

## 📁 Struktur Folder

```
app/
├── Http/
│   ├── Controllers/Api/
│   │   ├── AuthController.php       ✅ Done
│   │   ├── PaymentController.php    🔄 In Progress
│   │   ├── BuildingController.php   📋 Todo
│   │   ├── RoomController.php       📋 Todo
│   │   ├── TenantController.php     📋 Todo
│   │   ├── RentalController.php     📋 Todo
│   │   └── ActivityLogController.php 📋 Todo
│   ├── Middleware/
│   │   └── CheckRole.php            ✅ Done
│   └── Requests/
│       ├── LoginRequest.php
│       ├── StorePaymentRequest.php
│       └── VerifyPaymentRequest.php
├── Models/
│   ├── User.php                     ✅ Done (JWT)
│   ├── Payment.php                  📋 Todo
│   └── ...
└── Services/
    ├── AuthService.php
    ├── PaymentService.php
    └── ActivityLogService.php
```

---

## 📊 Sprint Progress

### Sprint 1 — Minggu 9 (Sekarang)

| Task | Status |
|---|---|
| Setup Laravel + koneksi DB | ✅ Done |
| Install & konfigurasi JWT | ✅ Done |
| CORS konfigurasi | ✅ Done |
| AuthController (login & logout) | ✅ Done |
| Middleware CheckRole | ✅ Done |
| Routes api.php | ✅ Done |
| Halaman Login Flutter | 📋 Todo |

### Sprint 1 — Minggu 10

| Task | Status |
|---|---|
| PaymentController — upload bukti | 📋 Todo |
| PaymentController — verifikasi & tolak | 📋 Todo |
| CRUD Gedung, Kamar, Penyewa | 📋 Todo |
| Halaman Upload Bukti (Flutter) | 📋 Todo |
| Halaman Verifikasi (Flutter) | 📋 Todo |

---

## 👥 Role & Akses

| Role | ID | Akses |
|---|---|---|
| administrator | 1 | Semua fitur + activity log |
| manager | 2 | Kelola gedung, kamar, penyewa, verifikasi pembayaran |
| penyewa | 3 | Upload pembayaran, lihat riwayat |

---

## 📦 Packages

| Package | Versi | Kegunaan |
|---|---|---|
| `laravel/framework` | 13.x | Framework utama |
| `tymon/jwt-auth` | latest | Autentikasi JWT |

---

> Last updated: Sprint 1 Minggu 9 — Mei 2026