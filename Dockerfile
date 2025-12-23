FROM php:8.4-cli

# Install MySQL PDO extension
RUN docker-php-ext-install pdo pdo_mysql

# Set working directory
WORKDIR /app

# Copy your project
COPY . .

# Expose port
EXPOSE 8080

# Install MySQL client
RUN apt-get update && apt-get install -y mariadb-client && rm -rf /var/lib/apt/lists/*

# Create startup script
RUN echo '#!/bin/bash\n\
if [ -n "$MYSQLHOST" ] && [ -n "$MYSQLUSER" ] && [ -n "$MYSQLPASSWORD" ] && [ -n "$MYSQLDATABASE" ]; then\n\
  echo "Setting up database..."\n\
  mysql -h $MYSQLHOST -u $MYSQLUSER -p$MYSQLPASSWORD $MYSQLDATABASE < schema.sql 2>/dev/null || echo "Database already initialized or connection failed"\n\
fi\n\
echo "Starting PHP server..."\n\
php -S 0.0.0.0:8080 -t public' > /app/start.sh && chmod +x /app/start.sh

# Run startup script
CMD ["/app/start.sh"]
