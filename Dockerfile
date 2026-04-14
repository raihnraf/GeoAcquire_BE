FROM php:8.2-cli

WORKDIR /app

# Install extensions needed for Laravel
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    ca-certificates \
    openssl \
    && rm -rf /var/lib/apt/lists/*

# Install MySQL extension for SSL support
RUN docker-php-ext-install pdo pdo_mysql mbstring exif zip bcmath

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Note: APP_KEY will be set via Environment Variables in Render
# Do NOT run key:generate or config:cache here - they need .env file

# Set permissions
RUN chown -R www-data:www-data /app \
    && chmod -R 755 /app/storage

EXPOSE 10000

CMD ["php", "artisan", "serve", "--host", "0.0.0.0", "--port", "10000"]
