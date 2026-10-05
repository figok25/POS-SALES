#!/usr/bin/env bash
# Update aplikasi di server setelah ada perubahan kode.
# Jalankan di server sebagai user deploy:   bash deploy/update.sh
#
# Catatan: folder public/build (hasil `npm run build`) TIDAK ada di Git. Kalau CSS/JS
# berubah, unggah ulang dari laptop dulu:
#   scp -r public/build deploy@IP_VPS:/var/www/pos-sales/public/

set -euo pipefail

APP_DIR="/var/www/pos-sales"
cd "$APP_DIR"

# Kalau ada langkah yang gagal, pastikan situs tidak tertinggal dalam mode maintenance.
trap 'echo "GAGAL -- mengaktifkan kembali situs"; php artisan up || true' ERR

php artisan down --retry=30

git pull --ff-only
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan optimize

php artisan up
echo "Selesai. Cek log bila perlu: tail -n 50 storage/logs/laravel.log"
