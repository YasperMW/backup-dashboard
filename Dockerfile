# Declare build argument for platform
ARG BUILDKIT_PLATFORM=linux/amd64

# Use a specific PHP base image tag for reliability
FROM --platform=$BUILDKIT_PLATFORM php:8.2-fpm-bullseye

# Install system dependencies (excluding nodejs and npm to avoid old versions)
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    sqlite3 \
    libsqlite3-dev \
&& apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Node.js 18 and npm from NodeSource
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - && \
    apt-get install -y nodejs && \
    npm install -g npm@9.8.1 && \
    node --version && \
    npm --version

# Install PHP extensions
RUN docker-php-ext-install pdo_sqlite mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy project files
COPY . .

# Install PHP dependencies
RUN composer install --optimize-autoloader --no-interaction --no-dev

# Install Node.js dependencies and build assets
RUN npm install && npm run build

# Create SQLite database file (if not exists) and set permissions
RUN touch /var/www/database/database.sqlite && \
    chown www-data:www-data /var/www/database/database.sqlite && \
    chmod 775 /var/www/database/database.sqlite && \
    chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Expose port for PHP-FPM
EXPOSE 9000

# Start PHP-FPM
CMD ["php-fpm"]