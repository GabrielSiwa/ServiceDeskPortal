FROM php:8.4-cli

# Install MySQL PDO extension
RUN docker-php-ext-install pdo pdo_mysql
RUN apt-get update && apt-get install -y mariadb-client && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /app
COPY . .

# Expose port
EXPOSE 8080

# Start the PHP built-in web server
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
