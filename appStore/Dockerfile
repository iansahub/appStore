# Use the official PHP image with Apache
FROM php:8.2-apache

# Set the working directory inside the container
WORKDIR /var/www/html

# Copy the application files into the container
COPY . /var/www/html

# Install required PHP extensions (if needed)
# Uncomment and modify the following line to install additional extensions
# RUN docker-php-ext-install mysqli pdo pdo_mysql

# Set proper permissions for the application
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Enable Apache mod_rewrite for clean URLs (if needed)
RUN a2enmod rewrite

# Expose port 80 for the Apache server
EXPOSE 80

# Start the Apache server
CMD ["apache2-foreground"]