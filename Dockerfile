# Use the PHP 8.2 base image
FROM php:8.2

# Update package lists and install required dependencies
RUN apt-get update -y && apt-get install -y openssl zip unzip libonig-dev

# Install Composer globally
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install PHP extensions required by Laravel
RUN docker-php-ext-install pdo mbstring

# Set the working directory to /app
WORKDIR /app

# Copy the entire project directory into the container at /app
COPY . /app

# Install project dependencies using Composer
RUN composer install

# Set the default command to run when the container starts
CMD php artisan serve --host=0.0.0.0 --port=8000

# Expose port 8000 to allow external access
EXPOSE 8000