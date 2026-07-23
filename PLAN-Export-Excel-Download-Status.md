# 📝 RENCANA IMPLEMENTASI FASE 4 — Export Excel Laporan Status Download Klien
**Project:** Portal Nenkin  
**Company:** PT Sumber Rezeki Exata Indonesia  
**Status:** PLAN PROPOSED (Awaiting User Approval — NO CODE WRITTEN IN THIS PHASE)

---

## 1. Hasil Investigasi Codebase & Prasyarat

### A. Definisi "Batch" di Codebase
Berdasarkan hasil penelusuran schema database dan Eloquent Models:
- **Definisi Batch:** 1 Batch adalah **1 Sesi Unggah Dokumen Masif oleh Admin** yang dicatat di tabel `document_upload_batchs` (memiliki `id`, `uploaded_by`, `total_files`, `matched_files`, `failed_files`, `completed_at`).
- Setiap file dokumen yang berhasil dicocokkan dan disimpan di tabel `documents` memiliki kolom `batch_id` yang terhubung langsung ke `document_upload_batchs.id`.
- Klien terikat ke batch melalui hubungan `Document::where('batch_id', $batchId)`.

### B. Library Excel yang Digunakan
- Menggunakan package **`maatwebsite/excel` (v3.1)** yang sudah terpasang di `composer.json`.
- Mengikuti arsitektur generator Excel yang sudah ada di codebase ([`app/Exports/CollectionExport.php`](file:///z:/03%20NEW/2026/19%20NENKIN%20ALL/app/Exports/CollectionExport.php)) dan helper ([`app/Helpers/ExportHelper.php`](file:///z:/03%20NEW/2026/19%20NENKIN%20ALL/app/Helpers/ExportHelper.php)). Tidak ada dependency baru yang perlu ditambahkan.

### C. Proteksi Role & Permission
- Fitur export ini mengolah data pendukung keputusan payroll.
- Proteksi route menggunakan middleware `auth` dan otorisasi permission `spatie/laravel-permission`:  
  `PermissionHelper::transform(PermissionHelper::ACCESS_ADMIN_UPLOAD_DOCUMENT, PermissionHelper::TYPE_READ)` (`admin_upload_document.read`).
- Pengguna tanpa permission `admin_upload_document.read` akan secara otomatis ditolak dengan `HTTP 403 Forbidden`.

---

## 2. Rancangan Struktur Kolom Laporan Excel

Untuk menjaga integritas data dan mencegah salah tafsir dalam perhitungan gaji, **laporan Excel TIDAK menggunakan kolom binary "Sudah Download: Ya/Tidak"**. Laporan akan menampilkan label *confidence* yang manusiawi (*human-readable*) sebagai berikut:

| Header Kolom | Tipe Data | Contoh Value | Penjelasan |
| :--- | :--- | :--- | :--- |
| **No** | Integer | `1` | Nomor urut baris. |
| **Client ID / No JP** | String | `KL1291` / `no_jp` | Nomor ID identitas klien. |
| **Nama Klien** | String | `RIZKY AJI SAPUTRA` | Nama lengkap klien. |
| **Batch ID & Tanggal** | String | `Batch #15 (2026-07-04)` | Informasi ID batch & tanggal upload admin. |
| **Document Type** | String | `Kwitansi` / `Resi` / `Ichijikin` / `General` | Jenis dokumen terkait. |
| **Sumber Logging (`source`)** | String | `live_tracked` / `proof_high` / `notified_only` | Kode teknis asal pencatatan data. |
| **Timestamp Kejadian** | Datetime | `2026-07-04 11:19:08` | Waktu spesifik download live / survei / email. |
| **Status Verification (Confidence)** | String | **Terverifikasi Real-Time (Download Klien)** | Nilai `source = live_tracked` (Aktivitas download langsung oleh klien). |
| | | **Terverifikasi via Survei/Ulasan** | Nilai `source = proof_high` (Terverifikasi aktif pasca dokumen lengkap). |
| | | **Baru Dinotifikasi (Belum Terverifikasi)** | Nilai `source = notified_only` (Email dikirim, belum ada aksi lanjutan). |

---

## 3. Rancangan Endpoint & Flow UI

### A. Endpoint Baru (Read-Only)
- **HTTP Method:** `GET`
- **Route Name:** `admin.documents.download-status.export`
- **URL Path:** `/admin/documents/batches/{batch}/download-status/export`
- **Controller Method:** `AdminDocumentController::exportDownloadStatus(DocumentUploadBatch $batch)`
- **Sifat Endpoint:** Murni *Read-Only*, menarik data dari `document_downloads` dan `documents` tanpa melakukan mutasi data apapun.

### B. Integrasi UI
- Menambahkan tombol **"Export Status Download (Excel)"** pada halaman detail batch di Admin Document Dashboard ([`resources/views/admin/documents/index.blade.php`](file:///z:/03%20NEW/2026/19%20NENKIN%20ALL/resources/views/admin/documents/index.blade.php)).

---

## 4. Estimasi Effort & Kompleksitas

| Komponen Fitur | Estimasi Effort | Kompleksitas |
| :--- | :---: | :---: |
| **Query Aggregation per Batch** | 2 Jam | Low |
| **Class Export (`DocumentDownloadStatusExport`)** | 2 Jam | Low |
| **Route & Permission Middleware Gate** | 1 Jam | Low |
| **Integrasi Tombol UI Admin Dashboard** | 1 Jam | Low |
| **Testing & Quality Assurance** | 2 Jam | Low |
| **TOTAL ESTIMASI EFFORT** | **8 Jam (1 Hari Kerja)** | **Low** |

---

🛑 **STATUS: PLAN PROPOSED — WAITING FOR USER APPROVAL**  
Sesuai instruksi, **TIDAK ADA KODE FITUR EXPORT YANG DITULIS ATAU DI-COMMIT PADA FASE INI**. Pengerjaan kode baru akan dimulai setelah rencana ini disetujui secara eksplisit oleh Anda.
