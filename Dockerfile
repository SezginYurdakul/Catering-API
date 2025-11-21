# Use official PHP CLI image
FROM php:8.2-cli

# Set working directory
WORKDIR /var/www/html

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_mysql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . /var/www/html

# Install PHP dependencies (without dev dependencies for production)
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Create logs directory if it doesn't exist
RUN mkdir -p logs && chmod -R 775 logs

# Expose port 80
EXPOSE 80

# Start PHP built-in server
CMD ["php", "-S", "0.0.0.0:80", "-t", "public"]
