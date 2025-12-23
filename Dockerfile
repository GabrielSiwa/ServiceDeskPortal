FROM php:8.4-cli

# Install MySQL PDO extension
RUN docker-php-ext-install pdo pdo_mysql

# Set working directory
WORKDIR /app

# Copy your project
COPY . .

# Expose port
EXPOSE 8080

# Create startup script
RUN echo '#!/bin/bash\nphp /app/src/migrations.php\nphp -S 0.0.0.0:8080 -t public' > /start.sh && chmod +x /start.sh

# Run startup script
CMD ["/start.sh"]
