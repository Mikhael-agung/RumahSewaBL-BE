# 🏠 Rumah Sewa Biru Laut — Backend

<div align="center">

![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![JWT](https://img.shields.io/badge/JWT-Auth-000000?style=for-the-badge&logo=jsonwebtokens&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Flutter](https://img.shields.io/badge/Flutter-Web-02569B?style=for-the-badge&logo=flutter&logoColor=white)
![Railway](https://img.shields.io/badge/Railway-Deploy-0B0D0E?style=for-the-badge&logo=railway&logoColor=white)

**Dokumentasi Backend — Sprint 1 Minggu 10**

🟢 Status: **CRUD API Ready**

[🌐 Live API](https://rumahsewabl-be-production-v2.up.railway.app) · [📋 Endpoints](#-api-endpoints) · [⚙️ Setup](#️-setup--instalasi) · [🧠 Logic Bisnis](#-logic-bisnis) · [🔀 Branch](#-git-branch)

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
| **BE Live** | `rumahsewabl-be-production-v2.up.railway.app` |

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
| `rooms` | Data kamar per gedung |
| `rentals` | Data penyewaan (kamar + penyewa) |
| `payment_deadlines` | Deadline pembayaran bulanan |
| `payments` | Data pembayaran + bukti upload |
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

## 🔄 Flow Backend

```
Request masuk
     │
     ▼
[Route] api.php
     │
     ├── Public? ──────────────────────────────► Controller langsung
     │
     └── Protected?
          │
          ▼
     [Middleware] auth:api  (JWT decode & validasi)
          │
          ▼
     [Middleware] CheckRole  (cek role: penyewa / manager / administrator)
          │
          ▼
     [FormRequest]  (validasi input, rules, unique check)
          │
          ▼
     [Controller]  (orchestrate, panggil model/service)
          │
          ├── Simple CRUD ──► [Model/Eloquent] ──► DB
          │
          └── Complex logic ──► [Service] ──► [Model/Eloquent] ──► DB
                                    │
                                    └── PaymentService (upload, verify, reject)
          │
          ▼
     Response JSON  { success, message, data }
```

### Struktur Response

Semua endpoint mengembalikan format konsisten:

```json
{
    "success": true,
    "message": "Pesan deskriptif",
    "data": { }
}
```

Error response:
```json
{
    "success": false,
    "message": "Pesan error",
    "data": null
}
```

---

## 🧠 Logic Bisnis

### Hierarki Data

```
Building (Gedung)
    └── Room (Kamar)
            └── Rental (Penyewaan)  ◄──── Tenant (Penyewa)
                        └── Payment (Pembayaran)
```

Urutan pembuatan data wajib mengikuti hierarki ini. Kamar butuh gedung, penyewaan butuh kamar + penyewa.

---

### 🏢 Gedung (Building)

| Rule | Detail |
|---|---|
| `building_code` | Unik, tidak boleh duplikat |
| Hapus gedung | ❌ Ditolak jika masih ada kamar aktif (soft delete belum terhapus) |

---

### 🚪 Kamar (Room)

| Field | Nilai valid |
|---|---|
| `room_status` | `available` / `occupied` / `maintenance` |

| Rule | Detail |
|---|---|
| `room_code` | Unik per sistem |
| Hapus kamar | ❌ Ditolak jika ada rental dengan status `active` |
| Status otomatis | Berubah ke `occupied` saat rental dibuat, kembali ke `available` saat rental `ended`/`cancelled` |

---

### 👤 Penyewa (Tenant)

| Rule | Detail |
|---|---|
| `tenant_code` | Unik |
| `email` | Unik |
| `user_id` | Opsional — jika penyewa punya akun login |
| Hapus penyewa | ❌ Ditolak jika masih ada rental `active` |

---

### 📋 Penyewaan (Rental)

| Field | Nilai valid |
|---|---|
| `rental_status` | `active` / `ended` / `cancelled` |

| Rule | Detail |
|---|---|
| Buat rental baru | Kamar harus berstatus `available`, otomatis berubah ke `occupied` |
| `created_by` | Diisi otomatis dari user yang login (manager/admin) |
| Update ke `ended`/`cancelled` | Kamar otomatis kembali ke `available` |
| Hapus rental | ❌ Ditolak jika status masih `active` — harus di-end/cancel dulu |

---

### 💳 Pembayaran (Payment)

| Field | Nilai valid |
|---|---|
| `payment_status` | `menunggu_verifikasi` / `terverifikasi` / `ditolak` |

| Rule | Detail |
|---|---|
| Upload bukti | Hanya role `penyewa`, file: jpg/jpeg/png/pdf, max 5MB |
| Validasi file | Magic bytes — cek header `%PDF-` untuk PDF, bukan hanya ekstensi |
| Verifikasi / Tolak | Hanya role `manager` atau `administrator` |
| Tolak pembayaran | Wajib menyertakan `rejection_reason` |

---

## 📡 API Endpoints

| Base | URL |
|---|---|
| **Production** | `https://rumahsewabl-be-production-v2.up.railway.app/api` |
| **Local** | `http://127.0.0.1:8000/api` |

### 🔓 Public

| Method | Endpoint | Deskripsi |
|---|---|---|
| `GET` | `/health` | Health check BE |
| `POST` | `/login` | Login user |

### 🔒 Protected — Semua Role (Bearer Token)

| Method | Endpoint | Deskripsi |
|---|---|---|
| `POST` | `/logout` | Logout |
| `GET` | `/me` | Data user yang sedang login |

### 🔒 Protected — Penyewa Only

| Method | Endpoint | Deskripsi |
|---|---|---|
| `POST` | `/payments/upload` | Upload bukti pembayaran |
| `GET` | `/payments/history` | Riwayat pembayaran milik sendiri |

### 🔒 Protected — Manager & Administrator

| Method | Endpoint | Deskripsi |
|---|---|---|
| `GET` | `/payments/pending` | List pembayaran menunggu verifikasi |
| `POST` | `/payments/{id}/verify` | Verifikasi pembayaran |
| `POST` | `/payments/{id}/reject` | Tolak pembayaran |
| `GET` | `/buildings` | List semua gedung + jumlah kamar |
| `POST` | `/buildings` | Tambah gedung baru |
| `GET` | `/buildings/{id}` | Detail gedung beserta kamar-kamarnya |
| `PUT` | `/buildings/{id}` | Update data gedung |
| `DELETE` | `/buildings/{id}` | Hapus gedung (jika tidak ada kamar aktif) |
| `GET` | `/rooms` | List semua kamar + info gedung |
| `POST` | `/rooms` | Tambah kamar baru |
| `GET` | `/rooms/{id}` | Detail kamar + riwayat penyewaan |
| `PUT` | `/rooms/{id}` | Update data kamar |
| `DELETE` | `/rooms/{id}` | Hapus kamar (jika tidak ada rental aktif) |
| `GET` | `/tenants` | List semua penyewa |
| `POST` | `/tenants` | Tambah penyewa baru |
| `GET` | `/tenants/{id}` | Detail penyewa + riwayat sewa |
| `PUT` | `/tenants/{id}` | Update data penyewa |
| `DELETE` | `/tenants/{id}` | Hapus penyewa (jika tidak ada rental aktif) |
| `GET` | `/rentals` | List semua penyewaan |
| `POST` | `/rentals` | Buat penyewaan baru |
| `GET` | `/rentals/{id}` | Detail penyewaan + pembayaran |
| `PUT` | `/rentals/{id}` | Update penyewaan / ubah status |
| `DELETE` | `/rentals/{id}` | Hapus penyewaan (non-aktif only) |

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
<summary><b>POST /api/buildings</b></summary>

**Request**
```json
{
    "building_code": "GDG-A",
    "building_name": "Gedung A",
    "building_address": "Jl. Contoh No. 1, Surabaya",
    "description": "Gedung utama lantai 3"
}
```

**Response**
```json
{
    "success": true,
    "message": "Gedung berhasil ditambahkan",
    "data": {
        "id": 1,
        "building_code": "GDG-A",
        "building_name": "Gedung A",
        "building_address": "Jl. Contoh No. 1, Surabaya",
        "description": "Gedung utama lantai 3",
        "created_at": "2026-05-25T10:00:00.000000Z"
    }
}
```
</details>

<details>
<summary><b>POST /api/rooms</b></summary>

**Request**
```json
{
    "building_id": 1,
    "room_code": "A-101",
    "monthly_price": 1500000,
    "room_status": "available",
    "notes": "Kamar sudut, ada AC"
}
```

**Response**
```json
{
    "success": true,
    "message": "Kamar berhasil ditambahkan",
    "data": {
        "id": 1,
        "building_id": 1,
        "room_code": "A-101",
        "monthly_price": "1500000.00",
        "room_status": "available",
        "notes": "Kamar sudut, ada AC",
        "building": {
            "id": 1,
            "building_name": "Gedung A"
        }
    }
}
```
</details>

<details>
<summary><b>POST /api/rentals</b></summary>

**Request**
```json
{
    "rental_code": "RNT-2026-001",
    "tenant_id": 1,
    "room_id": 1,
    "start_date": "2026-06-01",
    "end_date": "2027-06-01",
    "rental_status": "active"
}
```

**Response**
```json
{
    "success": true,
    "message": "Penyewaan berhasil dibuat",
    "data": {
        "id": 1,
        "rental_code": "RNT-2026-001",
        "tenant_id": 1,
        "room_id": 1,
        "start_date": "2026-06-01",
        "end_date": "2027-06-01",
        "rental_status": "active",
        "created_by": 2,
        "tenant": { "id": 1, "full_name": "Budi Santoso" },
        "room": {
            "id": 1,
            "room_code": "A-101",
            "room_status": "occupied",
            "building": { "id": 1, "building_name": "Gedung A" }
        }
    }
}
```
</details>

<details>
<summary><b>GET /api/buildings</b></summary>

**Response**
```json
{
    "success": true,
    "message": "Data gedung berhasil diambil",
    "data": [
        {
            "id": 1,
            "building_code": "GDG-A",
            "building_name": "Gedung A",
            "building_address": "Jl. Contoh No. 1, Surabaya",
            "description": "Gedung utama lantai 3",
            "rooms_count": 5,
            "created_at": "2026-05-25T10:00:00.000000Z",
            "updated_at": "2026-05-25T10:00:00.000000Z"
        }
    ]
}
```
</details>

<details>
<summary><b>GET /api/buildings/{id}</b></summary>

**Response**
```json
{
    "success": true,
    "message": "Detail gedung berhasil diambil",
    "data": {
        "id": 1,
        "building_code": "GDG-A",
        "building_name": "Gedung A",
        "building_address": "Jl. Contoh No. 1, Surabaya",
        "description": "Gedung utama lantai 3",
        "created_at": "2026-05-25T10:00:00.000000Z",
        "rooms": [
            {
                "id": 1,
                "room_code": "A-101",
                "monthly_price": "1500000.00",
                "room_status": "occupied",
                "notes": "Kamar sudut, ada AC"
            },
            {
                "id": 2,
                "room_code": "A-102",
                "monthly_price": "1200000.00",
                "room_status": "available",
                "notes": null
            }
        ]
    }
}
```
</details>

<details>
<summary><b>POST /api/buildings</b></summary>

**Request**
```json
{
    "building_code": "GDG-A",
    "building_name": "Gedung A",
    "building_address": "Jl. Contoh No. 1, Surabaya",
    "description": "Gedung utama lantai 3"
}
```

**Response**
```json
{
    "success": true,
    "message": "Gedung berhasil ditambahkan",
    "data": {
        "id": 1,
        "building_code": "GDG-A",
        "building_name": "Gedung A",
        "building_address": "Jl. Contoh No. 1, Surabaya",
        "description": "Gedung utama lantai 3",
        "created_at": "2026-05-25T10:00:00.000000Z"
    }
}
```
</details>

<details>
<summary><b>PUT /api/buildings/{id}</b></summary>

**Request** — kirim field yang ingin diubah saja
```json
{
    "building_name": "Gedung A (Renovasi)",
    "description": "Sedang dalam renovasi lantai 2"
}
```

**Response**
```json
{
    "success": true,
    "message": "Gedung berhasil diperbarui",
    "data": {
        "id": 1,
        "building_code": "GDG-A",
        "building_name": "Gedung A (Renovasi)",
        "building_address": "Jl. Contoh No. 1, Surabaya",
        "description": "Sedang dalam renovasi lantai 2",
        "updated_at": "2026-05-25T11:00:00.000000Z"
    }
}
```
</details>

<details>
<summary><b>DELETE /api/buildings/{id}</b></summary>

**Response — berhasil**
```json
{
    "success": true,
    "message": "Gedung berhasil dihapus",
    "data": null
}
```

**Response — ditolak (422)**
```json
{
    "success": false,
    "message": "Gedung tidak bisa dihapus karena masih memiliki kamar aktif",
    "data": null
}
```
</details>

---

<details>
<summary><b>GET /api/rooms</b></summary>

**Response**
```json
{
    "success": true,
    "message": "Data kamar berhasil diambil",
    "data": [
        {
            "id": 1,
            "building_id": 1,
            "room_code": "A-101",
            "monthly_price": "1500000.00",
            "room_status": "occupied",
            "notes": "Kamar sudut, ada AC",
            "building": {
                "id": 1,
                "building_code": "GDG-A",
                "building_name": "Gedung A"
            }
        }
    ]
}
```
</details>

<details>
<summary><b>GET /api/rooms/{id}</b></summary>

**Response**
```json
{
    "success": true,
    "message": "Detail kamar berhasil diambil",
    "data": {
        "id": 1,
        "room_code": "A-101",
        "monthly_price": "1500000.00",
        "room_status": "occupied",
        "notes": "Kamar sudut, ada AC",
        "building": {
            "id": 1,
            "building_name": "Gedung A"
        },
        "rentals": [
            {
                "id": 1,
                "rental_code": "RNT-2026-001",
                "rental_status": "active",
                "start_date": "2026-06-01",
                "end_date": "2027-06-01",
                "tenant": {
                    "id": 1,
                    "full_name": "Budi Santoso"
                }
            }
        ]
    }
}
```
</details>

<details>
<summary><b>POST /api/rooms</b></summary>

**Request**
```json
{
    "building_id": 1,
    "room_code": "A-101",
    "monthly_price": 1500000,
    "room_status": "available",
    "notes": "Kamar sudut, ada AC"
}
```

**Response**
```json
{
    "success": true,
    "message": "Kamar berhasil ditambahkan",
    "data": {
        "id": 1,
        "building_id": 1,
        "room_code": "A-101",
        "monthly_price": "1500000.00",
        "room_status": "available",
        "notes": "Kamar sudut, ada AC",
        "building": {
            "id": 1,
            "building_name": "Gedung A"
        }
    }
}
```
</details>

<details>
<summary><b>PUT /api/rooms/{id}</b></summary>

**Request** — kirim field yang ingin diubah saja
```json
{
    "monthly_price": 1750000,
    "room_status": "maintenance"
}
```

**Response**
```json
{
    "success": true,
    "message": "Kamar berhasil diperbarui",
    "data": {
        "id": 1,
        "room_code": "A-101",
        "monthly_price": "1750000.00",
        "room_status": "maintenance",
        "building": {
            "id": 1,
            "building_name": "Gedung A"
        }
    }
}
```
</details>

<details>
<summary><b>DELETE /api/rooms/{id}</b></summary>

**Response — berhasil**
```json
{
    "success": true,
    "message": "Kamar berhasil dihapus",
    "data": null
}
```

**Response — ditolak (422)**
```json
{
    "success": false,
    "message": "Kamar tidak bisa dihapus karena masih ada penyewaan aktif",
    "data": null
}
```
</details>

---

<details>
<summary><b>GET /api/tenants</b></summary>

**Response**
```json
{
    "success": true,
    "message": "Data penyewa berhasil diambil",
    "data": [
        {
            "id": 1,
            "tenant_code": "TNT-001",
            "full_name": "Budi Santoso",
            "phone_number": "081234567890",
            "email": "budi@email.com",
            "user_id": 3,
            "user": {
                "id": 3,
                "username": "budi_s"
            }
        }
    ]
}
```
</details>

<details>
<summary><b>GET /api/tenants/{id}</b></summary>

**Response**
```json
{
    "success": true,
    "message": "Detail penyewa berhasil diambil",
    "data": {
        "id": 1,
        "tenant_code": "TNT-001",
        "full_name": "Budi Santoso",
        "phone_number": "081234567890",
        "email": "budi@email.com",
        "user_id": 3,
        "user": {
            "id": 3,
            "username": "budi_s"
        },
        "rentals": [
            {
                "id": 1,
                "rental_code": "RNT-2026-001",
                "rental_status": "active",
                "start_date": "2026-06-01",
                "end_date": "2027-06-01",
                "room": {
                    "id": 1,
                    "room_code": "A-101",
                    "monthly_price": "1500000.00",
                    "building": {
                        "id": 1,
                        "building_name": "Gedung A"
                    }
                }
            }
        ]
    }
}
```
</details>

<details>
<summary><b>POST /api/tenants</b></summary>

**Request**
```json
{
    "tenant_code": "TNT-001",
    "full_name": "Budi Santoso",
    "phone_number": "081234567890",
    "email": "budi@email.com",
    "user_id": 3
}
```

> `user_id` opsional — isi jika penyewa sudah punya akun login

**Response**
```json
{
    "success": true,
    "message": "Penyewa berhasil ditambahkan",
    "data": {
        "id": 1,
        "tenant_code": "TNT-001",
        "full_name": "Budi Santoso",
        "phone_number": "081234567890",
        "email": "budi@email.com",
        "user_id": 3,
        "user": {
            "id": 3,
            "username": "budi_s"
        }
    }
}
```
</details>

<details>
<summary><b>PUT /api/tenants/{id}</b></summary>

**Request** — kirim field yang ingin diubah saja
```json
{
    "phone_number": "089876543210",
    "email": "budi.baru@email.com"
}
```

**Response**
```json
{
    "success": true,
    "message": "Penyewa berhasil diperbarui",
    "data": {
        "id": 1,
        "tenant_code": "TNT-001",
        "full_name": "Budi Santoso",
        "phone_number": "089876543210",
        "email": "budi.baru@email.com"
    }
}
```
</details>

<details>
<summary><b>DELETE /api/tenants/{id}</b></summary>

**Response — berhasil**
```json
{
    "success": true,
    "message": "Penyewa berhasil dihapus",
    "data": null
}
```

**Response — ditolak (422)**
```json
{
    "success": false,
    "message": "Penyewa tidak bisa dihapus karena masih memiliki penyewaan aktif",
    "data": null
}
```
</details>

---

<details>
<summary><b>GET /api/rentals</b></summary>

**Response**
```json
{
    "success": true,
    "message": "Data penyewaan berhasil diambil",
    "data": [
        {
            "id": 1,
            "rental_code": "RNT-2026-001",
            "rental_status": "active",
            "start_date": "2026-06-01",
            "end_date": "2027-06-01",
            "created_by": 2,
            "tenant": {
                "id": 1,
                "full_name": "Budi Santoso",
                "phone_number": "081234567890"
            },
            "room": {
                "id": 1,
                "room_code": "A-101",
                "monthly_price": "1500000.00",
                "building": {
                    "id": 1,
                    "building_name": "Gedung A"
                }
            },
            "created_by_user": {
                "id": 2,
                "username": "manager01"
            }
        }
    ]
}
```
</details>

<details>
<summary><b>GET /api/rentals/{id}</b></summary>

**Response**
```json
{
    "success": true,
    "message": "Detail penyewaan berhasil diambil",
    "data": {
        "id": 1,
        "rental_code": "RNT-2026-001",
        "rental_status": "active",
        "start_date": "2026-06-01",
        "end_date": "2027-06-01",
        "tenant": {
            "id": 1,
            "full_name": "Budi Santoso",
            "phone_number": "081234567890",
            "email": "budi@email.com"
        },
        "room": {
            "id": 1,
            "room_code": "A-101",
            "monthly_price": "1500000.00",
            "room_status": "occupied",
            "building": {
                "id": 1,
                "building_name": "Gedung A"
            }
        },
        "payments": [
            {
                "id": 5,
                "payment_code": "PAY-20260524-34AA3E",
                "payment_month": 6,
                "payment_year": "2026",
                "amount": "1500000.00",
                "payment_status": "terverifikasi"
            }
        ]
    }
}
```
</details>

<details>
<summary><b>POST /api/rentals</b></summary>

**Request**
```json
{
    "rental_code": "RNT-2026-001",
    "tenant_id": 1,
    "room_id": 1,
    "start_date": "2026-06-01",
    "end_date": "2027-06-01",
    "rental_status": "active"
}
```

**Response**
```json
{
    "success": true,
    "message": "Penyewaan berhasil dibuat",
    "data": {
        "id": 1,
        "rental_code": "RNT-2026-001",
        "rental_status": "active",
        "start_date": "2026-06-01",
        "end_date": "2027-06-01",
        "created_by": 2,
        "tenant": {
            "id": 1,
            "full_name": "Budi Santoso"
        },
        "room": {
            "id": 1,
            "room_code": "A-101",
            "room_status": "occupied",
            "building": {
                "id": 1,
                "building_name": "Gedung A"
            }
        }
    }
}
```

**Response — kamar tidak tersedia (422)**
```json
{
    "success": false,
    "message": "Kamar tidak tersedia untuk disewa",
    "data": null
}
```
</details>

<details>
<summary><b>PUT /api/rentals/{id} — update status</b></summary>

**Request** — contoh mengakhiri penyewaan
```json
{
    "rental_status": "ended"
}
```

> Saat status berubah ke `ended` atau `cancelled`, kamar otomatis kembali ke `available`

**Response**
```json
{
    "success": true,
    "message": "Penyewaan berhasil diperbarui",
    "data": {
        "id": 1,
        "rental_code": "RNT-2026-001",
        "rental_status": "ended",
        "start_date": "2026-06-01",
        "end_date": "2027-06-01",
        "room": {
            "id": 1,
            "room_code": "A-101",
            "room_status": "available"
        }
    }
}
```
</details>

<details>
<summary><b>DELETE /api/rentals/{id}</b></summary>

**Response — berhasil**
```json
{
    "success": true,
    "message": "Penyewaan berhasil dihapus",
    "data": null
}
```

**Response — ditolak (422)**
```json
{
    "success": false,
    "message": "Penyewaan aktif tidak bisa dihapus, ubah status terlebih dahulu",
    "data": null
}
```
</details>

---

<details>
<summary><b>GET /api/payments/pending</b></summary>

**Response**
```json
{
    "success": true,
    "message": "Data pembayaran pending berhasil diambil",
    "data": [
        {
            "id": 5,
            "payment_code": "PAY-20260524-34AA3E",
            "rental_id": 1,
            "payment_month": 6,
            "payment_year": "2026",
            "amount": "1500000.00",
            "payment_status": "menunggu_verifikasi",
            "proof_file": "storage/payment_proofs/bukti.jpg",
            "created_at": "2026-05-25T09:00:00.000000Z"
        }
    ]
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
    "data": {
        "id": 5,
        "payment_code": "PAY-20260524-34AA3E",
        "payment_status": "terverifikasi",
        "payment_month": 6,
        "payment_year": "2026",
        "amount": "1500000.00"
    }
}
```
</details>

<details>
<summary><b>POST /api/payments/{id}/reject</b></summary>

**Request**
```json
{
    "rejection_reason": "Bukti pembayaran tidak jelas, nominal tidak sesuai"
}
```

**Response**
```json
{
    "success": true,
    "message": "Pembayaran berhasil ditolak",
    "data": {
        "id": 5,
        "payment_code": "PAY-20260524-34AA3E",
        "payment_status": "ditolak",
        "rejection_reason": "Bukti pembayaran tidak jelas, nominal tidak sesuai"
    }
}
```
</details>

<details>
<summary><b>GET /api/payments/history</b></summary>

**Response**
```json
{
    "success": true,
    "message": "Riwayat pembayaran berhasil diambil",
    "data": [
        {
            "id": 5,
            "payment_code": "PAY-20260524-34AA3E",
            "payment_month": 6,
            "payment_year": "2026",
            "amount": "1500000.00",
            "payment_status": "terverifikasi",
            "proof_file": "storage/payment_proofs/bukti.jpg",
            "notes": null,
            "created_at": "2026-05-25T09:00:00.000000Z"
        }
    ]
}
```
</details>

---

## 👥 Role & Akses

| Role | Akses |
|---|---|
| `administrator` | Semua fitur + activity log |
| `manager` | Verifikasi pembayaran, CRUD gedung/kamar/penyewa/penyewaan |
| `penyewa` | Upload pembayaran, lihat riwayat milik sendiri |

---

## 📦 Dependencies

| Package | Versi | Kegunaan |
|---|---|---|
| `laravel/framework` | 13.x | Framework utama |
| `tymon/jwt-auth` | latest | Autentikasi JWT |

---

### Cara pakai

```bash
# Buat branch baru dari develop
git checkout develop
git pull origin develop
git checkout -b feature/manager/crud-building

# Setelah selesai, push dan buat PR ke develop
git push origin feature/manager/crud-building
```

---

## 📊 Sprint Progress

### Sprint 1 — Minggu 9 ✅

| Task | Status |
|---|---|
| Setup Laravel + koneksi DB | ✅ Done |
| Install & konfigurasi JWT | ✅ Done |
| CORS konfigurasi | ✅ Done |
| AuthController (login, logout, me) | ✅ Done |
| Middleware CheckRole | ✅ Done |
| Routes `api.php` | ✅ Done |
| Deploy BE ke Railway | ✅ Done |

### Sprint 1 — Minggu 10 ✅

| Task | Status |
|---|---|
| Models (Payment, Rental, Tenant, Room, Building) | ✅ Done |
| PaymentController — upload bukti | ✅ Done |
| PaymentController — verifikasi & tolak | ✅ Done |
| PaymentController — history & pending | ✅ Done |
| Validasi magic bytes PDF | ✅ Done |
| FormRequest CRUD (Building, Room, Tenant, Rental) | ✅ Done |
| CRUD Gedung (BuildingController) | ✅ Done |
| CRUD Kamar (RoomController) | ✅ Done |
| CRUD Penyewa (TenantController) | ✅ Done |
| CRUD Penyewaan (RentalController) | ✅ Done |
| Business logic: room status auto-update | ✅ Done |
| Business logic: guard delete aktif | ✅ Done |

### Sprint Berikutnya 📋

| Task | Status |
|---|---|
| ActivityLogService & ActivityLogController | 📋 Todo |
| Notifikasi sistem | 📋 Todo |
| Fix Railway IP whitelist Hostinger | 📋 Todo |
| AuthService | 📋 Todo |

---

<div align="center">

*Last updated: Sprint 1 Minggu 10 — Mei 2026*

</div>
