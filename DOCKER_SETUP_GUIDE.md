# 🐳 SongsLab Laravel API - Docker Setup Guide

Complete guide for setting up the Laravel API backend using Docker.

## 📋 What's Included

This Docker setup provides:

- ✅ **PHP 8.2-FPM** - Latest PHP with all required extensions
- ✅ **Nginx** - High-performance web server
- ✅ **MySQL 8.0** - Database server
- ✅ **Redis** - Cache and queue backend
- ✅ **Queue Worker** - Background job processing
- ✅ **Scheduler** - Cron job management
- ✅ **Composer** - Dependency management

## 🚀 Quick Start (3 Steps)

### Step 1: Navigate to the directory
```bash
cd laravel-api
```

### Step 2: Run the setup script
```bash
chmod +x setup.sh
./setup.sh
```

### Step 3: Access the API
Open your browser: **http://localhost:9000**

That's it! 🎉

## 🔌 Port Configuration

The following ports are used (avoiding your restricted ports):

| Service | Internal Port | External Port | Access URL |
|---------|--------------|---------------|------------|
| Nginx (API) | 80 | **9000** | http://localhost:9000 |
| MySQL | 3306 | **3307** | localhost:3307 |
| Redis | 6379 | **6380** | localhost:6380 |

**Note:** Ports 3000-3009, 8000, 8080, 8081, 8888, 8090 are avoided as requested.

## 📦 Container Architecture

```
┌─────────────────────────────────────────────────────────┐
│                    Docker Network                        │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌─────────┐ │
│  │  Nginx   │→ │   App    │→ │  MySQL   │  │  Redis  │ │
│  │  :9000   │  │ PHP-FPM  │  │  :3307   │  │  :6380  │ │
│  └──────────┘  └──────────┘  └──────────┘  └─────────┘ │
│                      ↓              ↓                    │
│                ┌──────────┐  ┌──────────┐               │
│                │  Queue   │  │Scheduler │               │
│                │  Worker  │  │  (Cron)  │               │
│                └──────────┘  └──────────┘               │
└─────────────────────────────────────────────────────────┘
```

## 🛠️ Using Makefile Commands

The easiest way to manage the Docker environment:

```bash
# View all available commands
make help

# Start containers
make up

# Stop containers
make down

# View logs
make logs

# Access shell
make shell

# Run migrations
make migrate

# Clear caches
make cache-clear

# Run tests
make test
```

## 📝 Common Tasks

### Installing Laravel Packages

```bash
# Install all required packages at once
make install-packages

# Or install individually
make composer cmd="require tymon/jwt-auth"
make composer cmd="require spatie/laravel-permission"
```

### Database Operations

```bash
# Run migrations
make migrate

# Fresh migration with seed data
make migrate-fresh

# Rollback last migration
make migrate-rollback

# Access MySQL CLI
make shell-db
```

### Development Workflow

```bash
# 1. Start containers
make up

# 2. Watch logs
make logs-app

# 3. Run migrations
make migrate

# 4. Clear caches after changes
make cache-clear

# 5. Run tests
make test
```

## 🔐 Database Credentials

Default credentials (can be changed in `docker-compose.yml`):

```
Host: localhost (or 'db' from within containers)
Port: 3307
Database: songslab
Username: songslab_user
Password: songslab_pass
Root Password: root_password
```

## 🌍 Environment Configuration

Edit `.env` file for configuration:

```env
# Application
APP_URL=http://localhost:9000

# Database (use 'db' as host for Docker network)
DB_HOST=db
DB_PORT=3306
DB_DATABASE=songslab
DB_USERNAME=songslab_user
DB_PASSWORD=songslab_pass

# Redis (use 'redis' as host for Docker network)
REDIS_HOST=redis
REDIS_PORT=6379

# Frontend URL (for CORS)
FRONTEND_URL=http://localhost:8000
```

## 🔧 Troubleshooting

### Issue: Containers won't start
```bash
# Check if ports are already in use
sudo lsof -i :9000
sudo lsof -i :3307
sudo lsof -i :6380

# Rebuild containers
make rebuild
```

### Issue: Permission denied errors
```bash
# Fix permissions
make permissions
```

### Issue: Database connection failed
```bash
# Check if database container is running
docker-compose ps

# View database logs
make logs-db

# Restart database
docker-compose restart db
```

### Issue: Changes not reflecting
```bash
# Clear all caches
make cache-clear

# Restart containers
make restart
```

## 📚 Next Steps

After successful setup:

1. ✅ **Configure JWT Authentication**
   ```bash
   make artisan cmd="vendor:publish --provider='Tymon\JWTAuth\Providers\LaravelServiceProvider'"
   make artisan cmd="jwt:secret"
   ```

2. ✅ **Create Database Schema**
   - Follow migrations in `LARAVEL_BACKEND_PLANNING.md`

3. ✅ **Set up API Routes**
   - Implement routes from planning document

4. ✅ **Configure AI Services**
   - Add API keys to `.env`

5. ✅ **Test API Endpoints**
   - Use Postman or curl

## 🎯 Production Deployment

For production, update:

1. Change `APP_ENV=production` in `.env`
2. Set `APP_DEBUG=false`
3. Use strong passwords
4. Configure SSL/HTTPS
5. Use external database (not Docker)
6. Set up proper backup strategy

## 📖 Additional Resources

- [Laravel Documentation](https://laravel.com/docs/11.x)
- [Docker Documentation](https://docs.docker.com/)
- [Project Planning Document](../LARAVEL_BACKEND_PLANNING.md)
- [Product Requirements](../SONGSLAB_PRD.md)

