# HRISKU APP - Sistem Informasi Kepegawaian

Aplikasi HRIS berbasis web untuk manajemen kepegawaian perusahaan.

## Tech Stack
- PHP 7.4+
- MySQL 5.7+
- HTML5, CSS3, JavaScript
- PDO untuk database

## Fitur MVP 1
✅ Authentication & Authorization (Role-based)
✅ Dashboard dengan statistik karyawan
✅ Manajemen Kepegawaian (CRUD)
✅ Manajemen Perusahaan, Cabang, Departemen
✅ Migration-based Database Versioning

## Installation

### Prerequisites
- XAMPP (Apache + MySQL)
- PHP 7.4 atau lebih tinggi
- Git

### Setup Steps

1. **Clone repository**
git clone https://github.com/username/hrisku-app.git
cd hrisku-app


2. **Copy ke folder htdocs**
Windows
cp -r hrisku-app C:\xampp\htdocs\


3. **Buat database**
- Buka phpMyAdmin: http://localhost/phpmyadmin
- Buat database: `hrisku_db`
- Collation: `utf8mb4_unicode_ci`

4. **Run migration**
cd C:\xampp\htdocs\hrisku-app
php scripts/migrate.php


5. **Akses aplikasi**
- URL: http://localhost/hrisku-app/modules/auth/login.php
- Default login: `admin` / `admin123`

## Database Migration
Run all pending migrations
php scripts/migrate.php

Check migration status
php scripts/migrate.php status

Fresh install (drop all tables)
php scripts/migrate.php fresh


## Struktur Folder
hrisku-app/
├── config/ # Database & config files
├── includes/ # Header, footer, navbar
├── modules/ # Feature modules
│ ├── auth/
│ ├── dashboard/
│ ├── kepegawaian/
│ └── company/
├── assets/ # CSS, JS, images
├── uploads/ # User uploads
├── migrations/ # Database migrations
└── scripts/ # Migration scripts


## User Roles
- **Super Admin** (Direktur): Full access
- **Admin** (Manager): Manage employees & company
- **User** (Staff/Supervisor/Koordinator): View only

## Roadmap
- [ ] Absensi (GPS & Face Recognition)
- [ ] Payroll & Komponen Gaji
- [ ] Permohonan (Cuti/Ijin/Lembur)
- [ ] Master Shift & Jadwal
- [ ] SPPD & Penugasan
- [ ] Asset Management
- [ ] Berita & Pengumuman

## License
Proprietary - All rights reserved

## Author
luciustori - 2024
