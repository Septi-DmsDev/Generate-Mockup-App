FROM php:8.2-apache

# Install dependencies yang sering dipakai aplikasi PHP (termasuk library untuk manipulasi gambar & zip)
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libwebp-dev \
    libzip-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Konfigurasi dan install PHP extensions (GD untuk gambar, MySQLi untuk database, Zip)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd mysqli pdo_mysql zip

# Mengaktifkan modul Apache Rewrite (berguna jika nanti pakai .htaccess)
RUN a2enmod rewrite

# Copy seluruh source code ke dalam folder root web server (Apache)
COPY . /var/www/html/

# Ubah permission agar Apache bisa membaca dan menulis file (misal untuk folder upload)
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose port 80 (standar web)
EXPOSE 80
