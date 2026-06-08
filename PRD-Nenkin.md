# PRD — Portal Nenkin Terpusat

**Versi:** 1.0  
**Tanggal:** Juni 2026  
**Tech Stack:** Laravel 11 + PHP 8.3, MySQL, S3/R2 Bucket  
**Tim:** Adam (Distribusi & Reputasi) · Yunus (Onboarding & Legal)  
**Timeline:** 7 Hari

---

## Daftar Isi

1. [Ringkasan Eksekutif](#1-ringkasan-eksekutif)
2. [Tujuan & Metrik Sukses](#2-tujuan--metrik-sukses)
3. [Ruang Lingkup](#3-ruang-lingkup)
4. [Peran Pengguna](#4-peran-pengguna)
5. [Alur Sistem Lengkap](#5-alur-sistem-lengkap)
6. [Spesifikasi Fitur](#6-spesifikasi-fitur)
7. [Skema Database](#7-skema-database)
8. [Spesifikasi API Endpoint](#8-spesifikasi-api-endpoint)
9. [Arsitektur Teknis](#9-arsitektur-teknis)
10. [Konvensi Penamaan File](#10-konvensi-penamaan-file)
11. [Business Rules & Validasi](#11-business-rules--validasi)
12. [Non-Functional Requirements](#12-non-functional-requirements)
13. [Sprint Plan 7 Hari](#13-sprint-plan-7-hari)
14. [Risiko & Mitigasi](#14-risiko--mitigasi)
15. [Open Questions](#15-open-questions)

---

## 1. Ringkasan Eksekutif

Portal Nenkin Terpusat adalah platform web internal yang mengkonsolidasikan seluruh alur operasional pencairan Nenkin ke dalam satu ekosistem digital. Sistem ini menggantikan mekanisme distribusi multi-tautan (Google Forms, broadcast manual) dengan satu portal terautentikasi yang mengotomasi klasifikasi klien, pengiriman dokumen, E-sign, dan manajemen reputasi.

**Masalah yang diselesaikan:**

- Fragmentasi channel komunikasi → friction & defisit kepercayaan klien
- Dana Speed Service dikirim sebelum E-sign → risiko finansial & legal
- Penyortiran data manual → pemborosan jam kerja tim
- Tidak ada tracking status → klien tidak tahu posisi proses mereka

---

## 2. Tujuan & Metrik Sukses

| Tujuan                              | Metrik                                          |
| ----------------------------------- | ----------------------------------------------- |
| Eliminasi multi-link broadcast      | 0 Google Form aktif pasca-launch                |
| Hard block Speed Service pre-E-sign | 0 kasus disbursement tanpa tanda tangan         |
| Otomasi distribusi dokumen          | Waktu distribusi < 30 menit per batch           |
| Transparansi status klien           | 100% klien dapat lihat status real-time         |
| Filter reputasi aktif               | Rating 1–3 tidak muncul di Google Review publik |

---

## 3. Ruang Lingkup

### In Scope

- Import data klien dari Excel Jepang (Ichijikin)
- Bulk Magic Link generation & broadcast list
- Registrasi klien via Magic Link (email + password)
- Mesin klasifikasi otomatis (Re-entry / Adjustment / Normal / Speed Service)
- Konfirmasi opt-in Speed Service via portal
- E-sign Agreement (refactor dari TypeScript)
- Upload & distribusi dokumen berbasis folder (ichijikin, resi, kwitansi)
- Dashboard klien: status tracker + document viewer
- Filter ulasan UPOD (intercept 1–3 bintang)
- Admin panel: manajemen klien, upload dokumen, monitoring

### Out of Scope

- OCR dokumen fisik Ichijikin
- Integrasi langsung ke sistem transfer/payment
- Mobile app (native iOS/Android)
- Notifikasi email otomatis (dapat ditambah sprint berikutnya)
- Multi-bahasa UI

---

## 4. Peran Pengguna

### 4.1 Admin Super

- Akses penuh seluruh sistem
- Import Excel & generate magic links
- Override klasifikasi klien
- Lihat semua review yang diintersep

### 4.2 PIC (Person In Charge) — Distribusi Dokumen

- Upload batch dokumen via folder structure
- Monitor status matching & upload
- Lihat failure log per batch

### 4.3 Agent Internal (Yunus / Adam)

- Lihat daftar klien & status per tahap
- Update status manual jika diperlukan
- Akses review yang diintersep untuk resolusi

### 4.4 Klien (End User)

- Registrasi via Magic Link
- Isi & konfirmasi data diri
- Opt-in Speed Service (jika eligible)
- Tanda tangan E-sign Agreement
- Lihat status proses real-time
- Download dokumen (ichijikin, resi, kwitansi)
- Submit ulasan setelah dokumen diterima

---

## 5. Alur Sistem Lengkap

```
[TAHAP 1 — ONBOARDING]
Admin upload Excel Jepang (Ichijikin)
        ↓
Sistem parse → seed tabel `clients`
        ↓
Sistem generate bulk `magic_links` (UUID, expires 14 hari)
        ↓
Admin export daftar link → broadcast via WA/Email ke klien

[TAHAP 2 — REGISTRASI KLIEN]
Klien klik Magic Link
        ↓
Validasi token (belum digunakan + belum expire)
        ↓
Form registrasi: email + password
        ↓
Akun terbuat → magic_link.used_at diisi → redirect Dashboard

[TAHAP 3 — KLASIFIKASI & KONFIRMASI]
Sistem auto-klasifikasi klien berdasarkan data Excel:
        → Re-entry / Adjustment / Normal / Speed Service
        ↓ (jika Normal/Re-entry/Adjustment)
Dashboard langsung aktif — menunggu dokumen
        ↓ (jika Speed Service eligible)
Klien dihadapkan halaman konfirmasi opt-in Speed Service
        → Tidak → klasifikasi turun ke Normal
        → Ya → lanjut ke E-sign

[TAHAP 4 — E-SIGN AGREEMENT (Speed Service only)]
Klien baca Agreement
        ↓
Klien tandatangan (checkbox + konfirmasi)
        ↓
Sistem rekam: signed_at, ip_address, user_agent, document_hash
        ↓
HARD BLOCK dilepas → Dashboard aktif penuh

[TAHAP 5 — DISTRIBUSI DOKUMEN]
PIC susun file dalam folder: /ichijikin/ /resi/ /kwitansi/
        ↓
PIC buka Admin Panel → Upload Batch (folder ZIP atau multi-upload)
        ↓
Sistem baca folder → tentukan document_type
Sistem normalize filename → match ke nama klien di DB
        ↓ (match berhasil)
Upload ke bucket → record di tabel `documents` → klien notifikasi
        ↓ (match gagal)
Log ke `document_upload_failures` → PIC review manual

[TAHAP 6 — DASHBOARD KLIEN]
Klien login → lihat status tracker (step 1–5)
Klien download dokumen yang sudah diterima
Klien klik "Beri Ulasan" setelah dokumen lengkap

[TAHAP 7 — FILTER REPUTASI]
Klien submit rating (1–5 bintang) + pesan opsional
        ↓
Rating 1–3 → intercept → kirim ke WA internal admin (via link WA)
Rating 4–5 → redirect ke URL Google Review publik
```

---

## 6. Spesifikasi Fitur

---

### F1 — Import Data Klien & Bulk Magic Link Generation

**Trigger:** Admin upload file Excel dari Jepang (Ichijikin data)

**Format Excel yang diterima:**

| Kolom         | Nama Field di DB | Tipe         | Keterangan                                  |
| ------------- | ---------------- | ------------ | ------------------------------------------- |
| Nama Lengkap  | `name`           | VARCHAR(255) | Wajib                                       |
| Tanggal Lahir | `dob`            | DATE         | Format: YYYY-MM-DD                          |
| No. WhatsApp  | `phone`          | VARCHAR(20)  | Opsional, untuk broadcast                   |
| Jenis Klaim   | `claim_type`     | ENUM         | Re-entry / Adjustment / Normal / Speed      |
| Data Tambahan | `raw_data`       | JSON         | Semua kolom lain dari Excel disimpan mentah |

**Proses:**

1. Validasi header Excel — jika kolom wajib tidak ada, tolak dengan pesan error spesifik
2. Skip baris duplikat (cek by `name` + `dob`) — log sebagai warning, tidak stop proses
3. Insert ke tabel `clients`
4. Generate `magic_link` per klien:
    - Token: `Str::uuid()`
    - Expires: 14 hari dari tanggal generate
    - URL format: `https://portal.domain.com/register/{token}`
5. Tampilkan summary: total imported, total skipped (duplikat), total links generated
6. Tombol "Export Daftar Link" → download CSV: [nama, no_wa, magic_link_url]

**Validasi:**

- File harus .xlsx atau .xls
- Baris tanpa nama atau tanggal lahir di-skip dan dicatat
- Maksimal 500 baris per upload (dapat dikonfigurasi)

---

### F2 — Registrasi via Magic Link

**Trigger:** Klien klik URL magic link dari WA/email

**Flow:**

1. Middleware `ValidateMagicLink` cek:
    - Token ada di DB? → jika tidak: halaman "Link tidak valid"
    - `used_at` null? → jika sudah digunakan: halaman "Link sudah digunakan. Silakan login."
    - `expires_at` > now? → jika expired: halaman "Link kedaluwarsa. Hubungi tim kami."
2. Redirect ke form registrasi dengan token sebagai parameter
3. Form menampilkan nama klien (pre-filled, read-only) sebagai konfirmasi identitas
4. Klien mengisi: Email + Password + Konfirmasi Password
5. Validasi email: format valid + belum terdaftar di sistem
6. Validasi password: minimal 8 karakter
7. Submit → buat `user` → tandai `magic_link.used_at = now()`
8. Auto-login → redirect ke Dashboard

**Catatan:**

- Token hanya bisa digunakan sekali
- Setelah used, klien harus login via halaman login biasa

---

### F3 — Autentikasi & Sesi

**Login:**

- Email + Password
- Session-based (bukan API token) — menggunakan Laravel Auth default
- Remember Me: opsional, 30 hari
- Redirect setelah login: Dashboard klien atau Admin Panel (berdasarkan role)

**Guard:**

- `web` guard untuk klien
- `admin` guard terpisah untuk admin/agent/PIC

**Middleware proteksi route:**

- `auth` → semua halaman klien
- `auth:admin` → semua halaman admin panel
- `esign.required` → custom middleware, cek apakah klien Speed Service sudah E-sign

---

### F4 — Mesin Klasifikasi Otomatis

**Trigger:** Saat data klien diimport dari Excel (Field `claim_type` dari Excel)

**Kategori & Deskripsi:**

| Kategori      | Kode         | Deskripsi                                                        |
| ------------- | ------------ | ---------------------------------------------------------------- |
| Re-entry      | `re_entry`   | Klien yang pernah mencairkan Nenkin sebelumnya dan masuk kembali |
| Adjustment    | `adjustment` | Klien dengan penyesuaian klaim (koreksi data atau jumlah)        |
| Normal        | `normal`     | Pencairan standar tanpa kondisi khusus                           |
| Speed Service | `speed`      | Layanan premium fast-track, memerlukan E-sign Agreement          |

**Aturan klasifikasi:** Diambil langsung dari kolom `claim_type` di Excel Jepang.  
Admin dapat override klasifikasi manual via Admin Panel.

**Catatan:** Jika kolom `claim_type` kosong atau tidak dikenali → default ke `normal`.

**Pengaruh klasifikasi:**

- `speed` → wajib E-sign sebelum dashboard aktif penuh
- `re_entry`, `adjustment`, `normal` → dashboard langsung aktif

---

### F5 — Speed Service Konfirmasi & E-Sign

**5A — Konfirmasi Opt-in Speed Service**

Setelah login pertama, klien Speed Service diarahkan ke halaman konfirmasi:

- Penjelasan singkat Speed Service (biaya, estimasi waktu, benefit)
- Pilihan: "Ya, saya ikut Speed Service" / "Tidak, lanjut Normal"
- Jika pilih Normal → update `classification` ke `normal` → masuk dashboard normal
- Jika pilih Ya → lanjut ke halaman E-sign

**5B — E-Sign Agreement**

**Konten Agreement:** File statis (HTML/PDF) yang berisi syarat & ketentuan Speed Service. Versi disimpan sebagai kode (misal: `v1.0`).

**Flow E-Sign:**

1. Tampilkan agreement (full scroll required sebelum tombol sign aktif — implementasi via JS)
2. Checkbox: "Saya telah membaca dan menyetujui ketentuan di atas"
3. Tombol "Tandatangani" aktif setelah checkbox dicentang
4. Klik → sistem rekam ke `esign_agreements`:
    - `signed_at`: timestamp server
    - `ip_address`: request IP
    - `user_agent`: browser/device info
    - `document_hash`: SHA256 dari konten agreement version saat ini
    - `agreement_version`: kode versi (misal: `v1.0`)
5. Flash message sukses → redirect Dashboard

**Hard Block Middleware (`esign.required`):**

- Cek: apakah klien `speed` dan belum ada record di `esign_agreements`?
- Jika ya → redirect paksa ke halaman E-sign, tidak bisa ke halaman lain
- Jika sudah sign → lolos

**Refactor dari TypeScript:**

- Pastikan hash calculation menggunakan SHA256 yang sama
- Pastikan audit trail fields (ip, user_agent, timestamp) identical

---

### F6 — Distribusi Dokumen

**6A — Upload Batch oleh PIC**

**Struktur folder yang diharapkan:**

```
batch_upload/
  ├── ichijikin/
  │     ├── YAMADA_TARO.pdf
  │     └── SUZUKI_HANAKO.pdf
  ├── resi/
  │     ├── YAMADA_TARO.pdf
  │     └── SUZUKI_HANAKO.pdf
  └── kwitansi/
        ├── YAMADA_TARO.pdf
        └── SUZUKI_HANAKO.pdf
```

**Mekanisme upload:**

- PIC upload ZIP berisi folder structure di atas **ATAU** multi-file upload per folder (tab terpisah)
- Sistem ekstrak/baca → tentukan `document_type` dari nama folder
- Tipe valid: `ichijikin`, `resi`, `kwitansi` (folder lain di-skip, dicatat sebagai warning)

**6B — Name Matching Algorithm**

```
Input:  filename = "YAMADA_TARO.pdf"
Step 1: Hapus ekstensi → "YAMADA_TARO"
Step 2: Normalize → lowercase + replace underscore/dash/titik dengan spasi → "yamada taro"
Step 3: Cari di `clients` tabel:
        a) Exact match (normalize nama klien sama) → confidence 1.0
        b) Fuzzy match dengan similar_text() → confidence 0.0–1.0
Step 4: Jika confidence >= 0.85 → match diterima
Step 5: Jika confidence < 0.85 → log ke `document_upload_failures`
```

**6C — Setelah Match Berhasil**

1. Upload file ke bucket (path: `documents/{client_id}/{type}/{filename}`)
2. Insert record ke tabel `documents`
3. Jika klien sudah login → dashboard mereka diperbarui real-time (atau saat refresh)

**6D — Batch Summary Report**

Setelah proses selesai, tampilkan ke PIC:

- Total file diproses
- Berhasil di-match & upload
- Gagal (dengan tabel: filename | folder | alasan gagal)
- Tombol download failure log (CSV)

**PIC dapat:**

- Re-assign file yang gagal secara manual ke klien yang benar
- Re-upload file setelah nama diperbaiki

---

### F7 — Dashboard Klien

**Komponen Status Tracker (seperti resi ekspedisi):**

```
[✓] Data Diterima        — tanggal import
[✓] Registrasi           — tanggal daftar
[ ] Verifikasi & Proses  — estimasi selesai
[ ] Dokumen Dikirim      — menunggu
[ ] Selesai              — menunggu
```

Status tracker diperbarui otomatis saat milestone berikut terpenuhi:
| Milestone | Kondisi Trigger |
|-----------|----------------|
| Data Diterima | `clients.created_at` terisi |
| Registrasi | `users.created_at` terisi |
| E-sign Selesai | `esign_agreements.signed_at` terisi (Speed only) |
| Dokumen Dikirim | Semua 3 dokumen ada di `documents` untuk klien ini |
| Selesai | `clients.status = completed` (admin set manual) |

**Komponen Dokumen:**

- Daftar dokumen: Ichijikin · Resi Transfer · Kwitansi
- Status per dokumen: Belum tersedia / Tersedia (tombol Download)
- File diunduh via signed URL dari bucket (expired 15 menit)

**Komponen Review (muncul setelah semua dokumen tersedia):**

- Prompt: "Bagaimana pengalaman Anda?"
- Rating bintang 1–5
- Textarea pesan (opsional)
- Submit → lihat F8

---

### F8 — Filter Reputasi UPOD

**Trigger:** Klien submit review dari dashboard

**Logic:**

```
Rating 1–3 → Status: "intercepted"
             → Tampilkan: "Terima kasih atas masukan Anda. Tim kami akan menghubungi Anda segera."
             → Generate link WA ke nomor internal:
               "wa.me/[NOMOR_ADMIN]?text=Review+dari+{nama_klien}+Rating+{rating}:+{pesan}"
             → Admin menerima notifikasi → resolusi krisis

Rating 4–5 → Status: "forwarded"
             → Redirect langsung ke Google Review URL
             → (URL Google Review dikonfigurasi di .env: GOOGLE_REVIEW_URL)
```

**Aturan:**

- Satu klien hanya bisa submit satu review
- Setelah submit, tombol review tidak muncul lagi

---

### F9 — Admin Panel

**Halaman Dashboard Admin:**

- Statistik: total klien, breakdown per klasifikasi, total dokumen terdistribusi, review pending resolusi

**Halaman Manajemen Klien:**

- Tabel klien: nama, klasifikasi, status, tanggal registrasi, status E-sign, status dokumen
- Filter: per klasifikasi, per status, per tanggal
- Pencarian: by nama
- Aksi per klien: lihat detail, override klasifikasi, regenerate magic link (jika expired)

**Halaman Upload Dokumen:**

- Form upload batch (ZIP atau multi-file per folder)
- History batch upload dengan status dan link failure log

**Halaman Review Terintercepted:**

- Daftar review 1–3 bintang: nama klien, rating, pesan, tanggal, status resolusi
- Tombol: "Tandai Resolved" + catatan resolusi

**Halaman Import Klien:**

- Form upload Excel
- Preview 5 baris pertama sebelum konfirmasi import
- Log import: sukses/skip/error per baris

---

## 7. Skema Database

```sql
-- TABEL UTAMA

CREATE TABLE clients (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    name_normalized VARCHAR(255) NOT NULL,       -- lowercase, stripped (untuk matching)
    dob             DATE,
    phone           VARCHAR(20),
    claim_type      ENUM('re_entry','adjustment','normal','speed') DEFAULT 'normal',
    status          ENUM('pending','active','completed') DEFAULT 'pending',
    raw_data        JSON,                         -- semua kolom Excel disimpan mentah
    source_file     VARCHAR(255),                 -- nama file Excel sumber
    imported_at     TIMESTAMP DEFAULT NOW(),
    created_at      TIMESTAMP DEFAULT NOW(),
    updated_at      TIMESTAMP DEFAULT NOW() ON UPDATE NOW(),
    INDEX idx_name_normalized (name_normalized),
    INDEX idx_claim_type (claim_type),
    INDEX idx_status (status)
);

CREATE TABLE magic_links (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id       BIGINT UNSIGNED NOT NULL,
    token           CHAR(36) NOT NULL UNIQUE,     -- UUID
    used_at         TIMESTAMP NULL DEFAULT NULL,
    expires_at      TIMESTAMP NOT NULL,
    created_at      TIMESTAMP DEFAULT NOW(),
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    INDEX idx_token (token)
);

CREATE TABLE users (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id       BIGINT UNSIGNED NULL UNIQUE,
    email           VARCHAR(255) NOT NULL UNIQUE,
    password        VARCHAR(255) NOT NULL,        -- bcrypt
    remember_token  VARCHAR(100) NULL,
    created_at      TIMESTAMP DEFAULT NOW(),
    updated_at      TIMESTAMP DEFAULT NOW() ON UPDATE NOW(),
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);

CREATE TABLE esign_agreements (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id           BIGINT UNSIGNED NOT NULL UNIQUE,
    agreement_version   VARCHAR(20) NOT NULL DEFAULT 'v1.0',
    signed_at           TIMESTAMP NOT NULL,
    ip_address          VARCHAR(45) NOT NULL,     -- support IPv6
    -- user_agent          TEXT NOT NULL,
    document_hash       CHAR(64) NOT NULL,        -- SHA256 hex
    created_at          TIMESTAMP DEFAULT NOW(),
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);

CREATE TABLE documents (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id           BIGINT UNSIGNED NOT NULL,
    type                ENUM('ichijikin','resi','kwitansi') NOT NULL,
    original_filename   VARCHAR(255) NOT NULL,
    bucket_path         VARCHAR(500) NOT NULL,
    match_confidence    DECIMAL(4,3) NOT NULL DEFAULT 1.000,
    batch_id            BIGINT UNSIGNED NULL,
    uploaded_by         BIGINT UNSIGNED NOT NULL, -- admin id
    uploaded_at         TIMESTAMP DEFAULT NOW(),
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    UNIQUE KEY uq_client_doc_type (client_id, type),  -- satu tipe per klien
    INDEX idx_client_id (client_id)
);

CREATE TABLE document_upload_batches (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uploaded_by     BIGINT UNSIGNED NOT NULL,
    total_files     INT UNSIGNED DEFAULT 0,
    matched_files   INT UNSIGNED DEFAULT 0,
    failed_files    INT UNSIGNED DEFAULT 0,
    status          ENUM('processing','completed','partial_failed','failed') DEFAULT 'processing',
    created_at      TIMESTAMP DEFAULT NOW(),
    completed_at    TIMESTAMP NULL
);

CREATE TABLE document_upload_failures (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    batch_id            BIGINT UNSIGNED NOT NULL,
    original_filename   VARCHAR(255) NOT NULL,
    folder_type         VARCHAR(50) NOT NULL,
    failure_reason      VARCHAR(500) NOT NULL,
    best_match_name     VARCHAR(255) NULL,        -- nama klien dengan confidence tertinggi
    best_match_score    DECIMAL(4,3) NULL,
    resolved_client_id  BIGINT UNSIGNED NULL,     -- diisi jika PIC assign manual
    resolved_at         TIMESTAMP NULL,
    created_at          TIMESTAMP DEFAULT NOW(),
    FOREIGN KEY (batch_id) REFERENCES document_upload_batches(id)
);

CREATE TABLE reviews (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id   BIGINT UNSIGNED NOT NULL UNIQUE,
    rating      TINYINT UNSIGNED NOT NULL,        -- 1–5
    message     TEXT NULL,
    status      ENUM('intercepted','forwarded','resolved') DEFAULT 'intercepted',
    resolved_by BIGINT UNSIGNED NULL,
    resolved_at TIMESTAMP NULL,
    notes       TEXT NULL,                        -- catatan resolusi internal
    submitted_at TIMESTAMP DEFAULT NOW(),
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);

-- TABEL ADMIN

-- CREATE TABLE admins (
--     id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
--     name        VARCHAR(255) NOT NULL,
--     email       VARCHAR(255) NOT NULL UNIQUE,
--     password    VARCHAR(255) NOT NULL,
--     role        ENUM('super_admin','pic','agent') NOT NULL DEFAULT 'agent',
--     created_at  TIMESTAMP DEFAULT NOW(),
--     updated_at  TIMESTAMP DEFAULT NOW() ON UPDATE NOW()
-- );
```

---

## 8. Spesifikasi API Endpoint

> Semua route web menggunakan session auth. Prefix `/admin` untuk admin panel.

### Public Routes

```
GET  /register/{token}        ValidateMagicLinkController@show
POST /register/{token}        ValidateMagicLinkController@store
GET  /login                   AuthController@showLoginForm
POST /login                   AuthController@login
POST /logout                  AuthController@logout
```

### Client Routes (auth middleware)

```
GET  /dashboard                    DashboardController@index
GET  /speed-confirm                SpeedConfirmController@show
POST /speed-confirm                SpeedConfirmController@store
GET  /esign                        EsignController@show
POST /esign                        EsignController@sign
GET  /documents/{type}/download    DocumentController@download   -- generate signed URL
POST /reviews                      ReviewController@store
```

### Admin Routes (auth:admin middleware)

```
GET  /admin/dashboard                      AdminDashboardController@index

-- Klien
GET  /admin/clients                        AdminClientController@index
GET  /admin/clients/{id}                   AdminClientController@show
PUT  /admin/clients/{id}/classify          AdminClientController@reclassify
POST /admin/clients/{id}/regenerate-link   AdminClientController@regenerateLink

-- Import
GET  /admin/import                         ImportController@show
POST /admin/import                         ImportController@store
GET  /admin/import/export-links            ImportController@exportLinks   -- download CSV

-- Dokumen
GET  /admin/documents                      AdminDocumentController@index
POST /admin/documents/upload               AdminDocumentController@upload
GET  /admin/documents/batches/{id}         AdminDocumentController@batchDetail
GET  /admin/documents/batches/{id}/failures/export  AdminDocumentController@exportFailures
POST /admin/documents/failures/{id}/resolve         AdminDocumentController@resolveFailure

-- Review
GET  /admin/reviews                        AdminReviewController@index
PUT  /admin/reviews/{id}/resolve           AdminReviewController@resolve
```

---

## 9. Arsitektur Teknis

### Stack

```
Framework   : Laravel 11 (PHP 8.3)
Database    : MySQL 8.0
Storage     : AWS S3 atau Cloudflare R2 (via Laravel Flysystem S3)
Frontend    : Blade + Alpine.js (minimal JS, tidak perlu Vue/React)
Queue       : Laravel Queue — driver: database (upgrade ke Redis jika perlu)
Auth        : Laravel Auth (dua guard: web & admin)
```

### Laravel Packages

```json
{
    "maatwebsite/excel": "^3.1",
    "league/flysystem-aws-s3-v3": "^3.0",
    "laravel/sanctum": "tidak diperlukan (session-based)"
}
```

### Struktur Folder (Key Files)

```
app/
  Http/
    Controllers/
      Auth/           AuthController, ValidateMagicLinkController
      Client/         DashboardController, EsignController, DocumentController, ReviewController
      Admin/          AdminClientController, ImportController, AdminDocumentController
    Middleware/
      ValidateMagicLink.php
      EsignRequired.php
  Services/
    MagicLinkService.php         -- generate, validate token
    ClassificationService.php    -- logic klasifikasi dari Excel data
    DocumentMatchingService.php  -- name normalization + fuzzy matching
    EsignService.php             -- hash calculation, record creation
    ReviewFilterService.php      -- intercept/forward logic
  Imports/
    ClientImport.php             -- Maatwebsite\Excel import class
config/
  app.php, filesystems.php, queue.php
resources/
  views/
    auth/             login.blade.php, register.blade.php
    client/           dashboard.blade.php, esign.blade.php, speed-confirm.blade.php
    admin/            (semua halaman admin)
    components/       status-tracker.blade.php, document-card.blade.php
routes/
  web.php             -- semua routes
```

### Signed URL untuk Download Dokumen

```php
// DocumentController@download
$url = Storage::disk('s3')->temporaryUrl(
    $document->bucket_path,
    now()->addMinutes(15)
);
return redirect($url);
```

---

## 10. Konvensi Penamaan File

### Aturan Penamaan (WAJIB diikuti PIC)

```
Format : {NAMA_DEPAN}_{NAMA_BELAKANG}.pdf
Contoh : YAMADA_TARO.pdf

- Huruf kapital semua
- Pisahkan nama dengan underscore (_)
- Untuk nama 3 kata: YAMADA_TARO_KENJI.pdf
- Tidak ada spasi, tanda titik, atau karakter khusus lainnya
- Ekstensi: .pdf (direkomendasikan), .jpg, .png (diterima)
```

### Normalisasi Matching (sisi sistem)

```php
function normalizeName(string $input): string {
    $name = strtolower($input);
    $name = pathinfo($name, PATHINFO_FILENAME); // hapus ekstensi
    $name = preg_replace('/[\-_\.\s]+/', ' ', $name); // underscore/dash → spasi
    $name = trim($name);
    return $name;
}

// "YAMADA_TARO.pdf" → "yamada taro"
// Nama klien "Yamada Taro" → "yamada taro"
// → EXACT MATCH ✓
```

### Fuzzy Matching Threshold

- `similar_text(normalized_filename, normalized_client_name) / max_length >= 0.85` → diterima
- Di bawah 0.85 → log ke failure, PIC assign manual

---

## 11. Business Rules & Validasi

| Rule                      | Detail                                                                                                                  |
| ------------------------- | ----------------------------------------------------------------------------------------------------------------------- |
| Magic link single-use     | Token yang sudah `used_at` tidak dapat dipakai ulang                                                                    |
| Magic link expiry         | 14 hari dari tanggal generate. Admin dapat regenerate                                                                   |
| Hard block E-sign         | Klien `speed` yang belum sign tidak bisa akses halaman lain kecuali `/esign` dan `/logout`                              |
| Satu review per klien     | Kolom `client_id` di `reviews` adalah UNIQUE — coba submit kedua kali ditolak                                           |
| Review prompt kondisional | Hanya muncul jika ketiga dokumen (ichijikin, resi, kwitansi) sudah ada di DB                                            |
| Dokumen overwrite         | Upload ulang tipe yang sama untuk klien yang sama akan REPLACE record lama (bukan duplikat)                             |
| Klasifikasi override      | Admin dapat ubah klasifikasi kapan saja — jika diubah dari `speed` ke `normal`, E-sign record tetap ada (tidak dihapus) |
| Signed URL expiry         | 15 menit — cukup untuk download, mencegah link tersebar                                                                 |

---

## 12. Non-Functional Requirements

| Aspek              | Requirement                                                             |
| ------------------ | ----------------------------------------------------------------------- |
| Keamanan password  | bcrypt, minimum 8 karakter                                              |
| HTTPS              | Wajib di production                                                     |
| E-sign audit trail | Immutable — tidak ada endpoint untuk hapus atau edit `esign_agreements` |
| File upload limit  | 50MB per file, 500MB per batch ZIP                                      |
| Format dokumen     | PDF (primary), JPG, PNG                                                 |
| Signed URL expiry  | 15 menit                                                                |
| Queue timeout      | 300 detik untuk batch document processing                               |
| Browser support    | Chrome, Firefox, Safari terbaru                                         |
| Response time      | Halaman utama < 2 detik                                                 |
| Bucket path        | `documents/{client_id}/{type}/{filename}`                               |

---

## 13. Sprint Plan 7 Hari

### Yunus — Domain: Onboarding & Legal

**Hari 1 — Foundation**

- [ ] Laravel project setup, konfigurasi .env
- [ ] Buat semua migrations (lihat Skema Database)
- [ ] Setup dua Auth guard: `web` (klien) + `admin`
- [ ] Login/logout controller untuk kedua guard

**Hari 2 — Import & Magic Link**

- [ ] `ClientImport.php` dengan Maatwebsite Excel
- [ ] `MagicLinkService`: generate bulk + validasi token
- [ ] Admin route: halaman upload Excel + preview + konfirmasi
- [ ] Export CSV daftar magic link

**Hari 3 — Registrasi Klien**

- [ ] Middleware `ValidateMagicLink`
- [ ] Form registrasi (email + password, nama pre-filled)
- [ ] `ValidateMagicLinkController`: show + store
- [ ] Flash messages + error states

**Hari 4 — Klasifikasi & Admin Client Management**

- [ ] `ClassificationService` (logic dari data Excel)
- [ ] Admin Panel: tabel klien, filter, pencarian
- [ ] Halaman detail klien: semua status, override klasifikasi, regenerate link

**Hari 5 — Speed Service & E-Sign**

- [ ] Halaman konfirmasi Speed Service (opt-in / turun ke normal)
- [ ] Middleware `EsignRequired` — hard block
- [ ] `EsignController`: tampilkan agreement, proses sign
- [ ] `EsignService`: SHA256 hash, record audit trail
- [ ] Refactor logic dari TypeScript — pastikan hash konsisten

---

### Adam — Domain: Distribusi & Reputasi

**Hari 1–2 — Storage & Upload Infrastructure**

- [ ] Konfigurasi S3/R2 di Laravel Filesystems
- [ ] Admin route: halaman upload batch dokumen
- [ ] Terima ZIP upload → ekstrak ke temp folder
- [ ] Baca struktur folder → identifikasi `document_type`

**Hari 3 — Name Matching Engine**

- [ ] `DocumentMatchingService`: normalisasi nama
- [ ] Exact match + fuzzy match (`similar_text`, threshold 0.85)
- [ ] Proses via Laravel Queue (Job: `ProcessDocumentBatch`)
- [ ] Insert hasil ke `documents`, `document_upload_failures`

**Hari 4 — Batch Reporting & Manual Resolve**

- [ ] Batch summary page: total, sukses, gagal
- [ ] Failure log table dengan best_match suggestion
- [ ] Form resolve manual: PIC assign file gagal ke klien yang benar

**Hari 5 — Client Dashboard & Download**

- [ ] `DashboardController`: kalkulasi status tracker
- [ ] Blade component `status-tracker.blade.php`
- [ ] Document cards: status + tombol download
- [ ] `DocumentController@download`: generate signed URL

**Hari 6 — UPOD Review Filter**

- [ ] Form rating (1–5 bintang + textarea)
- [ ] `ReviewFilterService`: intercept vs forward logic
- [ ] Generate WA link untuk review terintercepted
- [ ] Admin panel: tabel review terintercepted + resolve

**Hari 7 — Integration, Testing & Deploy**

- [ ] End-to-end test seluruh flow: import → register → esign → upload dokumen → download → review
- [ ] Fix bugs dari testing
- [ ] Setup production environment
- [ ] Deploy + smoke test

---

## 14. Risiko & Mitigasi

| Risiko                                    | Dampak                                   | Mitigasi                                                                                      |
| ----------------------------------------- | ---------------------------------------- | --------------------------------------------------------------------------------------------- |
| Nama file tidak sesuai konvensi           | Dokumen tidak ter-match → manual effort  | Briefing PIC sebelum go-live + failure log yang jelas + manual resolve UI                     |
| Queue stuck di batch besar                | Dokumen tidak terproses                  | Set timeout 300s, monitor failed jobs, tombol retry batch di admin                            |
| Magic link dikirim ke nomor WA yang salah | Orang lain bisa register atas nama klien | Konfirmasi nama saat registrasi (read-only) + PIC verifikasi nomor WA di Excel sebelum import |
| E-sign agreement versi berubah            | Hash lama tidak match                    | Simpan `agreement_version` di DB — hash diikat ke versi, bukan konten dinamis                 |
| File bucket terlalu besar                 | Biaya storage membengkak                 | Monitor ukuran file saat upload, set limit 50MB per file                                      |
| Dua klien nama identik                    | Dokumen ke klien yang salah              | Tambah DOB atau ID unik ke nama file jika terjadi konflik: `YAMADA_TARO_19900101.pdf`         |

---

## 15. Open Questions

> Item berikut perlu keputusan sebelum atau selama sprint berlangsung.

| #   | Pertanyaan                                                                     | Pemilik | Deadline |
| --- | ------------------------------------------------------------------------------ | ------- | -------- |
| 1   | Nomor WA admin untuk menerima review intercepted (env: `ADMIN_WA_NUMBER`)      | Adam    | Hari 1   |
| 2   | URL Google Review publik (env: `GOOGLE_REVIEW_URL`)                            | Adam    | Hari 1   |
| 3   | Konten Agreement E-sign versi 1.0 — siapa yang menulis?                        | Yunus   | Hari 3   |
| 4   | Bucket provider: AWS S3 atau Cloudflare R2? Credentials siapa yang setup?      | Adam    | Hari 1   |
| 5   | Apakah PIC upload ZIP atau multi-file upload per folder? (pengaruhi UI upload) | Adam    | Hari 2   |
| 6   | Apakah ada SLA waktu proses yang perlu ditampilkan ke klien di status tracker? | Yunus   | Hari 4   |

---

_Dokumen ini hidup — update setiap ada keputusan baru selama sprint berlangsung._
