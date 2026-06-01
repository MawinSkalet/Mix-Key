# Multi-stage Dockerfile for Laravel 10 Backend

# --- Base Stage ---
FROM php:8.4-fpm AS base

WORKDIR /var/www/html

# Install System Dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_pgsql pgsql mbstring exif pcntl bcmath gd opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# --- Development Stage ---
FROM base AS dev

# Expose PHP built-in server port or serve on port 80
EXPOSE 80

# Auto-install composer dependencies if missing, then keep running php artisan serve in dev
CMD sh -c "if [ ! -f vendor/autoload.php ]; then composer install; fi && php artisan serve --host=0.0.0.0 --port=80"

# --- Production Stage ---
FROM base AS production

# Copy existing application directory contents
COPY . /var/www/html

# Run composer installation for production
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Adjust file permissions for Laravel storage and cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port 80
EXPOSE 80

# Run standard server in production
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=80"]
