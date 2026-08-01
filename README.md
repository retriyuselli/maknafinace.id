# maknafinace.id

## Local setup

Project ini berjalan normal di local MAMP dengan konfigurasi berikut:

- MySQL host: `127.0.0.1`
- MySQL port: `8889`
- Database: `maknafinance_demo`
- Username: `root`
- Password: `root`

## Catatan MAMP

Di mesin ini MySQL MAMP tidak listen di port default `3306`, tetapi di `8889`.
Kalau `.env` masih memakai `3306`, command artisan yang ikut boot provider aplikasi bisa gagal dengan error `Connection refused`.

Binary MySQL yang terdeteksi:

```bash
/Applications/MAMP/Library/bin/mysql80/bin/mysql
```

## Menyiapkan database lokal

Kalau database belum ada, buat dulu:

```bash
/Applications/MAMP/Library/bin/mysql80/bin/mysql -h 127.0.0.1 -P 8889 -u root -proot -e "CREATE DATABASE IF NOT EXISTS maknafinance_demo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Lalu jalankan migrasi:

```bash
php artisan migrate
```
