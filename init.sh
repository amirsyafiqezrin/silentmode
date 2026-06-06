#!/bin/bash
set -e

echo "Starting Docker Container Init Process..."

# Check if composer dependencies are installed
if [ ! -d "vendor" ]; then
    echo "Vendor directory not found. Running composer install..."
    composer install --no-interaction --no-progress
else
    echo "Vendor directory exists. Skipping composer install."
fi

# Check if .env exists
if [ ! -f ".env" ]; then
    echo "Copying .env.example to .env..."
    cp .env.example .env
    
    echo "Generating application key..."
    php artisan key:generate
fi

# Run migrations and seed the database
echo "Running migrations and seeding database..."
php artisan migrate --force
php artisan db:seed --force

echo "Setup complete! Starting PHP built-in server..."
# We execute whatever command is passed to the container, or default to artisan serve
if [ $# -eq 0 ]; then
    exec php artisan serve --host=0.0.0.0 --port=8000
else
    exec "$@"
fi
