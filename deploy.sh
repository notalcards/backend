#!/bin/bash
set -e

echo "=== Deploying NatalCharts ==="

# Backend
cd /var/www/natalcharts/backend
git pull origin main
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Frontend
cd /var/www/natalcharts/frontend
git pull origin main
npm install --production=false
npm run build
pm2 restart natalcharts-frontend || pm2 start npm --name natalcharts-frontend -- start -- -p 3001

echo "=== Done ==="
