# 🏠 Rumah Sewa Biru Laut — Backend

<div align="center">

![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![JWT](https://img.shields.io/badge/JWT-Auth-000000?style=for-the-badge&logo=jsonwebtokens&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Flutter](https://img.shields.io/badge/Flutter-Web-02569B?style=for-the-badge&logo=flutter&logoColor=white)
![Railway](https://img.shields.io/badge/Railway-Deploy-0B0D0E?style=for-the-badge&logo=railway&logoColor=white)

**Dokumentasi Backend — Sprint 1 Minggu 10**

🟢 Status: **Payment API Ready**

[🌐 Live API](https://rumahsewabl-be-production.up.railway.app) · [📋 Endpoints](#-api-endpoints) · [⚙️ Setup](#️-setup--instalasi)

</div>

---

## 📋 Informasi Project

| Field | Detail |
|---|---|
| **Project** | Rumah Sewa Biru Laut |
| **Mata Kuliah** | Manajemen Proyek Teknologi Informasi |
| **Stack BE** | Laravel 13 + JWT Auth |
| **Stack FE** | Flutter Web |
| **Database** | MySQL (Hostinger) |
| **BE Live** | rumahsewabl-be-production-v2.up.railway.app |

---

## 🗄️ Database

| Field | Value |
|---|---|
| **Host** | `auth-db1417.hstgr.io` |
| **Database** | `u271192176_rsbl_test` |
| **Local Dev** | MySQL via Laragon `127.0.0.1:3306` |

### Tabel

| Tabel | Keterangan |
|---|---|
| `roles` | Role user: `administrator`, `manager`, `penyewa` |
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

### Langkah Instalasi

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

# 6. Storage link
php artisan storage:link

# 7. Jalankan server
php artisan serve
```

### Konfigurasi `.env`

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

```
Flutter  ──POST /api/login──►  Laravel  ──validasi users──►  JWT Token
                                                                  │
Flutter  ◄──token──────────────────────────────────────────────────
   │
   └── setiap request: Authorization: Bearer <token>
                              │
                       Laravel decode → valid → proses
```

---

## 📡 API Endpoints

| Base | URL |
|---|---|
| **Production** | `https://rumahsewabl-be-production.up.railway.app/api` |
| **Local** | `http://127.0.0.1:8000/api` |

### 🔓 Public

| Method | Endpoint | Deskripsi |
|---|---|---|
| `GET` | `/health` | Health check BE |
| `POST` | `/login` | Login user |

### 🔒 Protected (Bearer Token)

| Method | Endpoint | Role | Deskripsi |
|---|---|---|---|
| `POST` | `/logout` | Semua | Logout |
| `GET` | `/me` | Semua | Data user login |
| `POST` | `/payments/upload` | penyewa | Upload bukti pembayaran |
| `GET` | `/payments/history` | penyewa | Riwayat pembayaran |
| `GET` | `/payments/pending` | manager, admin | List pembayaran pending |
| `POST` | `/payments/{id}/verify` | manager, admin | Verifikasi pembayaran |
| `POST` | `/payments/{id}/reject` | manager, admin | Tolak pembayaran |

---

## 📨 Contoh Request & Response

<details>
<summary><b>POST /api/login</b></summary>

**Request**
```json
{
    "username": "admin01",
    "password": "admin123"
}
```

**Response**
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
</details>

<details>
<summary><b>POST /api/payments/upload</b></summary>

**Request** — `multipart/form-data`

| Field | Type | Keterangan |
|---|---|---|
| `payment_month` | integer | Bulan (1–12) |
| `payment_year` | integer | Tahun |
| `amount` | numeric | Nominal pembayaran |
| `notes` | string | Opsional |
| `proof_file` | file | jpg/jpeg/png/pdf, max 5MB |

**Response**
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
        "payment_status": "menunggu_verifikasi"
    }
}
```
</details>

<details>
<summary><b>POST /api/payments/{id}/verify</b></summary>

**Response**
```json
{
    "success": true,
    "message": "Pembayaran berhasil diverifikasi",
    "data": { ... }
}
```
</details>

<details>
<summary><b>POST /api/payments/{id}/reject</b></summary>

**Request**
```json
{
    "rejection_reason": "Bukti pembayaran tidak jelas"
}
```

**Response**
```json
{
    "success": true,
    "message": "Pembayaran berhasil ditolak",
    "data": { ... }
}
```
</details>

---

## 👥 Role & Akses

| Role | Akses |
|---|---|
| `administrator` | Semua fitur + activity log |
| `manager` | Verifikasi pembayaran, kelola gedung/kamar/penyewa |
| `penyewa` | Upload pembayaran, lihat riwayat |

---

## 📦 Dependencies

| Package | Versi | Kegunaan |
|---|---|---|
| `laravel/framework` | 13.x | Framework utama |
| `tymon/jwt-auth` | latest | Autentikasi JWT |

---

## 📊 Sprint Progress

### Sprint 1 — Minggu 9 ✅

| Task | Status |
|---|---|
| Setup Laravel + koneksi DB | ✅ Done |
| Install & konfigurasi JWT | ✅ Done |
| CORS konfigurasi | ✅ Done |
| AuthController (login & logout) | ✅ Done |
| Middleware CheckRole | ✅ Done |
| Routes `api.php` | ✅ Done |
| Deploy BE ke Railway | ✅ Done |

### Sprint 1 — Minggu 10 🔄

| Task | Status |
|---|---|
| Models (Payment, Rental, Tenant, Room, Building) | ✅ Done |
| PaymentController — upload bukti | ✅ Done |
| PaymentController — verifikasi & tolak | ✅ Done |
| PaymentController — history & pending | ✅ Done |
| Validasi magic bytes PDF | ✅ Done |
| CRUD Gedung, Kamar, Penyewa, Penyewaan | 📋 Todo |

---

<div align="center">

*Last updated: Sprint 1 Minggu 10 — Mei 2026*

</div>
