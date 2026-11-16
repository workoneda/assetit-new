# SIM-ASET IT RSUD — IT Asset Management System
**Sistem Inventarisasi dan Monitoring Aset Teknologi Informasi Rumah Sakit**

Aplikasi berbasis web modern dengan desain SaaS Dark Mode (Navy/Dark Blue) untuk mengelola seluruh inventaris perangkat TI di lingkungan rumah sakit.

## 1. Teknologi yang Digunakan
- **Backend**: Laravel 11 (PHP 8.3+)
- **Frontend**: Vue 3 + Vue Router 4 + Pinia (SPA)
- **Database**: MySQL 8.x (`it_asset_db`) — Satu-satunya database utama
- **Styling & UI**: Tailwind CSS + Lucide Icons + Chart.js (`vue-chartjs`)
- **QR Code & Excel**: `qrcode` (Cetak Label 40×20 mm) & `xlsx` (SheetJS Export/Import Excel)
- **Deployment**: Mendukung Server Lokal (Laragon/Nginx/Apache) & VPS menggunakan **Docker** (`Dockerfile` & `docker-compose.yml`)

## 2. Akun Login Default
| Role | Email | Username | Password | Hak Akses |
|---|---|---|---|---|
| **Administrator** | `admin@rsud.go.id` | `admin` | `password123` | Akses penuh seluruh fitur, manajemen pengguna, backup & reset produksi |
| **Petugas IT** | `petugas@rsud.go.id` | `petugas` | `password123` | Kelola data aset, catat pemeliharaan, lihat laporan, cetak QR |

## 3. Cara Menjalankan di Server Lokal
```bash
# 1. Jalankan migrasi & seeder MySQL
php artisan migrate --seed

# 2. Build aset frontend Vue 3
npm run build

# 3. Jalankan server aplikasi
php artisan serve --port=8000
```

## 4. Cara Menjalankan dengan Docker (VPS / Server Lokal)
```bash
docker compose up -d --build
docker exec -it sim_aset_app php artisan migrate --seed
```
Akses aplikasi pada `http://localhost:8080`.
# assetit-new
