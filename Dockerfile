# Use the PHP 8.2 base image
FROM php:8.2

# Update package lists and install required dependencies
RUN apt-get update -y \
    && DEBIAN_FRONTEND=noninteractive apt-get install -y \
        cmake \
        git \
        libcurl4-openssl-dev \
        libonig-dev \
        libxml2-dev \
        openssl \
        unzip \
        zip \
    && rm -rf /var/lib/apt/lists/*

# Install Composer globally
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install PHP extensions required by Laravel and Couchbase SDK usage
RUN docker-php-ext-install dom mbstring pdo \
    && printf "\n" | pecl install couchbase \
    && docker-php-ext-enable couchbase

# Set the working directory to /app
WORKDIR /app

# Copy the entire project directory into the container at /app
COPY . /app

# Install project dependencies using Composer
RUN composer install --no-interaction --prefer-dist --optimize-autoloader     && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/testing storage/framework/views storage/logs bootstrap/cache

# Set the default command to run when the container starts
CMD php artisan serve --host=0.0.0.0 --port=8000

# Expose port 8000 to allow external access
EXPOSE 8000
