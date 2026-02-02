.PHONY: help setup build up down restart logs shell artisan composer test migrate fresh

help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Available targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  %-15s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

setup: ## Initial setup - build containers and install Laravel
	@chmod +x setup.sh
	@./setup.sh

build: ## Build Docker containers
	docker-compose build

up: ## Start all containers
	docker-compose up -d
	@echo "✅ Containers started!"
	@echo "API: http://localhost:9000"

down: ## Stop all containers
	docker-compose down
	@echo "✅ Containers stopped!"

restart: ## Restart all containers
	docker-compose restart
	@echo "✅ Containers restarted!"

logs: ## View logs from all containers
	docker-compose logs -f

logs-app: ## View application logs
	docker-compose logs -f app

logs-nginx: ## View nginx logs
	docker-compose logs -f nginx

logs-db: ## View database logs
	docker-compose logs -f db

shell: ## Access application container shell
	docker-compose exec app /bin/bash

shell-db: ## Access MySQL CLI
	docker-compose exec db mysql -u songslab_user -psongslab_pass songslab

artisan: ## Run artisan command (usage: make artisan cmd="migrate")
	docker-compose exec app php artisan $(cmd)

composer: ## Run composer command (usage: make composer cmd="install")
	docker-compose exec app composer $(cmd)

test: ## Run tests
	docker-compose exec app php artisan test

migrate: ## Run database migrations
	docker-compose exec app php artisan migrate

migrate-fresh: ## Fresh migration with seed
	docker-compose exec app php artisan migrate:fresh --seed

migrate-rollback: ## Rollback last migration
	docker-compose exec app php artisan migrate:rollback

seed: ## Run database seeders
	docker-compose exec app php artisan db:seed

cache-clear: ## Clear all caches
	docker-compose exec app php artisan cache:clear
	docker-compose exec app php artisan config:clear
	docker-compose exec app php artisan route:clear
	docker-compose exec app php artisan view:clear
	@echo "✅ All caches cleared!"

permissions: ## Fix storage permissions
	docker-compose exec app chown -R songslab:songslab /var/www/storage /var/www/bootstrap/cache
	docker-compose exec app chmod -R 775 /var/www/storage /var/www/bootstrap/cache
	@echo "✅ Permissions fixed!"

install-packages: ## Install required Laravel packages
	docker-compose exec app composer require tymon/jwt-auth
	docker-compose exec app composer require spatie/laravel-permission
	docker-compose exec app composer require spatie/laravel-medialibrary
	docker-compose exec app composer require intervention/image
	docker-compose exec app composer require league/flysystem-aws-s3-v3
	docker-compose exec app composer require openai-php/laravel
	docker-compose exec app composer require laravel/horizon
	docker-compose exec app composer require spatie/laravel-backup
	@echo "✅ All packages installed!"

clean: ## Remove all containers, volumes, and images
	docker-compose down -v
	@echo "✅ Cleaned up!"

rebuild: ## Rebuild containers from scratch
	docker-compose down -v
	docker-compose build --no-cache
	docker-compose up -d
	@echo "✅ Containers rebuilt!"

status: ## Show container status
	docker-compose ps

