#!/bin/bash

echo "Starting Silentmode Project Setup..."

# Install composer dependencies using Docker if vendor folder does not exist
if [ ! -d "vendor" ]; then
    echo "Installing PHP dependencies..."
    docker run --rm \
        -u "$(id -u):$(id -g)" \
        -v "$(pwd):/var/www/html" \
        -w /var/www/html \
        laravelsail/php8.2-composer:latest \
        composer install --ignore-platform-reqs
fi

# Create .env if not exists
if [ ! -f ".env" ]; then
    echo "Creating .env file..."
    cp .env.example .env
fi

# Start Docker containers using Docker Compose
echo "Starting Docker containers..."
docker compose up -d

# Wait for MySQL to initialize properly
echo "Waiting for database to initialize (10 seconds)..."
sleep 10

# Generate application key and migrate database with seeds
echo "Setting up Laravel application..."
docker compose exec -T laravel.test php artisan key:generate
docker compose exec -T laravel.test php artisan migrate:fresh --seed

echo "=========================================="
echo "Setup Complete! The application is running."
echo "=========================================="
