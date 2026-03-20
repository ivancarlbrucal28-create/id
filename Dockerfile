FROM php:8.2-apache

# Install Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

# Set working directory
WORKDIR /var/www/html

# Copy package.json and install JS dependencies
COPY package*.json ./
RUN npm install

# Copy composer files if you use it
# COPY composer.json composer.lock ./
# RUN composer install

# Copy all project files
COPY . .

# Give Apache access
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
