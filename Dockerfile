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
RUN docker-php-ext-install pdo_sqlite mbstring exif pcntl bcmath gd zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy project files
COPY . .

# Install PHP dependencies
RUN composer install --optimize-autoloader --no-interaction --no-dev

# Install Node.js dependencies and build assets for production
RUN npm install && \
    npm run build && \
    npm cache clean --force

# Create SQLite database file (if not exists) and set permissions
RUN touch /var/www/database/database.sqlite && \
    chown www-data:www-data /var/www/database/database.sqlite && \
    chmod 775 /var/www/database/database.sqlite && \
    chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Configure PHP to allow large uploads and long-running requests
# This writes an ini file in conf.d so it is automatically loaded by PHP-FPM
RUN set -eux; \
    printf "post_max_size=4096M\n"        >  /usr/local/etc/php/conf.d/uploads.ini; \
    printf "upload_max_filesize=4096M\n"  >> /usr/local/etc/php/conf.d/uploads.ini; \
    printf "memory_limit=4096M\n"         >> /usr/local/etc/php/conf.d/uploads.ini; \
    printf "max_execution_time=0\n"       >> /usr/local/etc/php/conf.d/uploads.ini; \
    printf "max_input_time=0\n"           >> /usr/local/etc/php/conf.d/uploads.ini; \
    printf "file_uploads=On\n"            >> /usr/local/etc/php/conf.d/uploads.ini

# Expose port for PHP-FPM
EXPOSE 9000

# Start PHP-FPM