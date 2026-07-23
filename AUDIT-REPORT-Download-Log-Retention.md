# 📄 AUDIT REPORT — Download Log Retention & Historical Data Availability
**Project:** Portal Nenkin  
**Company:** PT Sumber Rezeki Exata Indonesia  
**Date:** 2026-07-23  
**Status:** FASE 1 COMPLETED (Read-Only Audit)

---

## 1. Status Ketersediaan Log per Sumber

### 1.1 Supabase Storage & Infrastructure Log
- **Project Ref:** `utjtgnzjgdusisquzhue`
- **Organization Plan:** **Pro Tier** (`plan: "pro"`, Organization ID: `floiqirapapumpzlgtmf`)
- **Storage Bucket:** `speed-service` (Private S3 Bucket)
- **Supabase Management Logs Explorer API:** **Tersedia**
- **Analisis `storage.objects` DB Table:**
  - Column `last_accessed_at` ada pada schema Postgres (`storage.objects`).
  - **Hasil Audit Query SQL:** Dari seluruh objek file dokumen di database, `last_accessed_at` nilainya persis sama dengan `created_at` (100% dari 0 objek yang ter-update setelah upload).
  - **Penyebab Teknis:** Protokol S3 (`Storage::disk('supabase')->get()`) yang digunakan oleh `DocumentController::download()` tidak melakukan `UPDATE` row pada tabel Postgres `storage.objects` demi efisiensi I/O database.
- **Supabase Management API / HTTP Logs (`service: api`):**
  - Berhasil menarik HTTP request log yang merekam pembacaan file S3 Storage (`GET /storage/v1/s3/speed-service/documents/...`).
  - **Window Retention:** Log HTTP ini diputar (rolling retention) dalam kurun waktu **24 jam s.d. 7 hari** pada dashboard default Supabase Pro Tier. Request historis untuk periode payroll lampau (> 7 hari) yang belum di-export ke Log Drain eksternal **sudah terotasi (flushed)** dari buffer aktif Supabase.

### 1.2 Railway Application Server Log
- **Project ID:** `NENKIN SPEED` (`3411fb6b-8fe2-4f3c-8b27-99ea03d7feb3`)
- **Environment:** `production` (`8cf47dc2-8e23-4eff-a7ac-b2321d1474dd`)
- **Service Name:** `exa-20-speed` (`c0d42187-c6b8-4548-84d8-83d3db06f6ea`)
- **HTTP Log Stream:** **Tersedia** via Railway GraphQL & Log API.
- **Window Retention:** Railway menyimpan HTTP Access Logs secara ephemeral/rolling (maksimal 100 s.d. 1000 buffer lines / 7 hari tergantung volume traffic).
- **Hasil Audit Log:** Request HTTP ke `/agreement/download` dan `/dashboard` terekam pada log aktif terbaru, namun request historis ke `/documents/{type}/download` untuk periode payroll bulan-bulan sebelumnya **sudah terhapus dari log buffer**.

---

## 2. Data Mentah yang Berhasil Ditarik

Data log mentah yang berhasil ditangkap dari Supabase Management API (`service: api`) untuk event pembacaan dokumen via S3 protocol telah disimpan ke file JSON terpisah:

- **Path File Audit Log:** [`audit/supabase-storage-access-log-2026-07.json`](file:///z:/03%20NEW/2026/19%20NENKIN%20ALL/audit/supabase-storage-access-log-2026-07.json)
- **Contoh Sample Record Log:**
```json
{
  "event_message": "GET | 200 | https://utjtgnzjgdusisquzhue.storage.supabase.co/storage/v1/s3/speed-service/documents/985/ichijikin/YOGI%20SUHADA_4190118580.jpg | aws-sdk-php/3.384.4 ua/2.1 OS/Linux#6.18.5+deb13-cloud-amd64 lang/php#8.3.32 m/P,Z,e,N,b GuzzleHttp/7",
  "id": "4f801cdc-f2f5-4015-ac96-c040fa484d26",
  "identifier": "utjtgnzjgdusisquzhue",
  "method": "GET",
  "path": "/storage/v1/s3/speed-service/documents/985/ichijikin/YOGI%20SUHADA_4190118580.jpg",
  "status_code": 200,
  "timestamp": 1784691277580000,
  "iso_datetime": "2026-07-22T11:34:37.580Z",
  "source": "supabase_api_log"
}
```

---

## 3. Rekomendasi Alternatif & Strategic Plan

Dikarenakan log server aplikasi dan storage untuk periode payroll historis lampau sudah ter-rotasi (terhapus secara otomatis oleh rentang retensi log 7 hari bawaan cloud provider), berikut adalah rekomendasi alternatif konkret yang diurutkan berdasarkan tingkat kelayakan (*feasibility*):

### 1. Fallback Signal Proksi di Database yang Tersedia Saat Ini (Paling Feasible untuk Historis)
Untuk keperluan review payroll periode lalu, sistem dapat menggunakan komposit data sinyal proksi di database `PT Sumber Rezeki Exata Indonesia`:

| Sumber Data Proksi | Kolom / Tabel | Jumlah Record | Tingkat Kepercayaan (*Confidence*) | Penjelasan Trade-off |
| :--- | :--- | :---: | :---: | :--- |
| **Email Notifikasi Dokumen** | `send_email_logs.created_at`<br/>(`DocumentAvailableNotification`) | **638** | **Medium** | Membuktikan notifikasi email bahwa 3 dokumen telah lengkap dikirimkan ke email klien. |
| **Magic Link Access** | `client_magic_links.used_at` | **2,340** | **Medium** | Membuktikan klien berhasil login/buka link portal pasca dokumen di-upload. |
| **Survei / Ulasan Klien** | `upod_submissions` & `reviews` | **317** | **High (Indikasi Akses)** | Form ini hanya muncul di UI jika klien membuka dashboard dengan dokumen lengkap. |
| **User Session Activity** | `sessions.last_activity` | **141** | **Low-Medium** | Membuktikan klien aktif di web portal, namun tidak mencatat tombol spesifik yang diklik. |

### 2. Memulai Live Tracking Bersih (Clean Baseline) mulai Fase 2
Pilihan terbaik dan paling valid secara hukum & audit internal adalah mengimplementasikan **Fase 2 (Live Database Logging)** sehingga mulai payroll cycle berikutnya, setiap aktivitas klik/download dokumen oleh klien tercatat 100% presisi (*hard evidence*).

### 3. Pengajuan Request Dump Log ke Support Supabase / Railway (Jika Diperlukan Sengketa Formal)
Apabila terdapat kebutuhan sengketa audit yang mewajibkan bukti hard log lampau (> 7 hari lalu), admin PT Sumber Rezeki Exata Indonesia dapat mengajukan tiket support ke Supabase / Railway untuk menarik *cold storage log archive* jika masih tersimpan di infrastruktur mereka.

### 4. Konfigurasi Log Drains / Long-term Log Retention (Untuk Masa Depan)
Menghubungkan Supabase & Railway ke Log Drain Service (seperti Datadog, Better Stack, atau AWS S3 Log Drain) untuk menyimpan HTTP & Storage access logs hingga 1-3 tahun sesuai standar audit operasional perusahaan.

---

## 🛑 STATUS: AWAITING USER REVIEW & APPROVAL BEFORE PHASE 2

Fase 1 (Audit & Laporan Retention) telah selesai dilaksanakan tanpa mengubah kode aplikasi apapun. Mohon review laporan di atas sebelum menyetujui eksekusi **FASE 2 (Pembuatan Tabel Migration `document_downloads` & Live Logging di `DocumentController.php`)**.
