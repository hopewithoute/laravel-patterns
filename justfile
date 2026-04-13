# Laravel + Vue Development Commands
# Run `just` to see all available commands

# Default: list available commands
default:
    @just --list

# Start all development servers (backend + frontend)
dev:
    @echo "🚀 Starting development servers..."
    @just backend & just frontend

# Start Laravel backend server
backend:
    @echo "🔧 Starting Laravel server on http://localhost:8000"
    php artisan serve

# Start frontend development server (Vite)
frontend:
    @echo "📦 Starting Vite dev server..."
    npm run dev

# Build frontend for production
build:
    @echo "🏗️  Building frontend assets..."
    npm run build

# Run database migrations
migrate:
    @echo "📊 Running migrations..."
    php artisan migrate

# Run database migrations with fresh
migrate-fresh:
    @echo "🔄 Fresh migration with seeders..."
    php artisan migrate:fresh --seed

# Run PHPUnit tests
test:
    @echo "🧪 Running tests..."
    php artisan test

# Run tests with coverage
test-coverage:
    @echo "🧪 Running tests with coverage..."
    php artisan test --coverage

# Clear all Laravel caches
clear-cache:
    @echo "🧹 Clearing all caches..."
    php artisan cache:clear
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear

# Cache all Laravel configs
cache-all:
    @echo "⚡ Caching configs..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

# Run Laravel Pint (code style fixer)
pint:
    @echo "✨ Running Pint..."
    ./vendor/bin/pint

# Run Prettier (code style fixer)
prettier:
    @echo "🎨 Running Prettier..."
    npm run format

# Run ESLint (code linter)
eslint:
    @echo "🔍 Running ESLint..."
    npm run lint

# Run all linters (Pint + ESLint)
lint: pint eslint

# Install PHP dependencies
composer-install:
    @echo "📦 Installing PHP dependencies..."
    composer install

# Install Node dependencies
npm-install:
    @echo "📦 Installing Node dependencies..."
    npm install

# Install all dependencies
install: composer-install npm-install
    @echo "✅ All dependencies installed!"

# Run Tinker (Laravel REPL)
tinker:
    php artisan tinker

# Generate application key
key-generate:
    @echo "🔑 Generating app key..."
    php artisan key:generate

# Create symlink for storage
storage-link:
    @echo "🔗 Creating storage link..."
    php artisan storage:link

# Watch logs
logs:
    @echo "📋 Watching Laravel logs..."
    tail -f storage/logs/laravel.log

# Run queue worker
queue:
    @echo "⚙️  Starting queue worker..."
    php artisan queue:work

# Start development with queue worker
dev-full:
    @echo "🚀 Starting full development environment..."
    @just backend & just frontend & just queue
