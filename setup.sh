#!/bin/bash

echo "🚀 Setting up SongsLab Laravel API with Docker..."

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo "❌ Docker is not installed. Please install Docker first."
    exit 1
fi

# Check if Docker Compose is installed
if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose is not installed. Please install Docker Compose first."
    exit 1
fi

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    echo "📝 Creating .env file from .env.example..."
    cp .env.example .env
else
    echo "✅ .env file already exists"
fi

# Build and start Docker containers
echo "🐳 Building Docker containers..."
docker-compose build

echo "🚀 Starting Docker containers..."
docker-compose up -d

# Wait for MySQL to be ready
echo "⏳ Waiting for MySQL to be ready..."
sleep 10

# Install Laravel if not already installed
if [ ! -f "composer.json" ]; then
    echo "📦 Installing Laravel 11..."
    docker-compose exec -T app composer create-project --prefer-dist laravel/laravel .
    
    # Copy .env file
    docker-compose exec -T app cp .env.example .env
fi

# Install Composer dependencies
echo "📦 Installing Composer dependencies..."
docker-compose exec -T app composer install

# Generate application key
echo "🔑 Generating application key..."
docker-compose exec -T app php artisan key:generate

# Run migrations
echo "🗄️  Running database migrations..."
docker-compose exec -T app php artisan migrate

# Set permissions
echo "🔐 Setting permissions..."
docker-compose exec -T app chown -R songslab:songslab /var/www/storage /var/www/bootstrap/cache

echo ""
echo "✅ Setup complete!"
echo ""
echo "📋 Container Information:"
echo "   - API URL: http://localhost:9000"
echo "   - MySQL: localhost:3307"
echo "   - Redis: localhost:6380"
echo ""
echo "🔧 Useful commands:"
echo "   - Start containers: docker-compose up -d"
echo "   - Stop containers: docker-compose down"
echo "   - View logs: docker-compose logs -f"
echo "   - Run artisan: docker-compose exec app php artisan [command]"
echo "   - Run composer: docker-compose exec app composer [command]"
echo ""

