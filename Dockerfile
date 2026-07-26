FROM php:8.2-apache

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy application files to Apache root
COPY . /var/www/html/

# Set working directory & permissions
WORKDIR /var/www/html
RUN chown -R www-data:www-data /var/www/html \
    && chmod +x /var/www/html/docker-entrypoint.sh

# Expose default HTTP port
EXPOSE 80

# Entrypoint script binds Apache to Render's dynamic $PORT
ENTRYPOINT ["/var/www/html/docker-entrypoint.sh"]
