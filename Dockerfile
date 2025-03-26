# Use the official PHP 8.3 image with Apache
FROM php:8.3-apache

# Set the working directory inside the container
WORKDIR /var/www/html

# Copy all application files from the host to the container
COPY . /var/www/html

# Install required PHP extensions
# - mysqli: for MySQL database connections
# - pdo and pdo_mysql: for PDO-based database interactions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Set proper permissions for Apache to access the application files
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Enable Apache's mod_rewrite for URL rewriting
RUN a2enmod rewrite

# Expose port 80 for HTTP traffic
EXPOSE 80