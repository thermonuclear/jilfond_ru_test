#!/bin/bash
echo "Starting jilfond_ru_test project..."

docker compose up -d --build

echo "Installing dependencies..."
docker compose exec php composer install --no-interaction

echo "Copying environment files..."
docker compose exec php cp .env.example .env || true

echo "Generating application key..."
docker compose exec php php artisan key:generate --no-interaction

echo "Running migrations..."
docker compose exec php php artisan migrate --force

echo "Seeding database..."
docker compose exec php php artisan db:seed

echo ""
echo "================================"
echo " Project is ready!"
echo " App:        http://localhost:8080"
echo " phpMyAdmin: http://localhost:8081"
echo "================================"
echo ""
echo "To start the queue worker: make queue"
echo "To run tests: make test"
echo ""
