# Use official PHP image with Apache
FROM php:8.1-apache

# Install required PHP extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache modules
RUN a2enmod rewrite headers

# Configure Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Set up Apache configuration for the site
COPY docker/apache-site.conf /etc/apache2/sites-available/000-default.conf

# Copy application files
COPY . /var/www/html/

# Create necessary directories and set permissions
RUN mkdir -p /var/www/html/data \
    && mkdir -p /var/www/html/admin/data \
    && mkdir -p /var/www/html/admin/data/logs \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/data \
    && chmod -R 777 /var/www/html/admin/data

# Create .htaccess for data directory protection
RUN echo "Deny from all" > /var/www/html/data/.htaccess \
    && echo "Deny from all" > /var/www/html/admin/data/.htaccess

# Set up PHP configuration
RUN echo "upload_max_filesize = 50M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 50M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini

# Note: This site uses API endpoints configured in the admin panel
# No sample data files are needed

EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]