FROM php:8.4-cli

# Install MySQL PDO extension
RUN docker-php-ext-install pdo pdo_mysql

# Set working directory
WORKDIR /app

# Copy your project
COPY . .

# Expose port
EXPOSE 8080

# Run migrations on startup
RUN php src/migrations.php || true

# Run built-in PHP server
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
