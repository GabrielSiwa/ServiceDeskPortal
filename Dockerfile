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
set -e\n\
echo "Environment variables:"\n\
echo "MYSQLHOST=$MYSQLHOST"\n\
echo "MYSQLUSER=$MYSQLUSER"\n\
echo "MYSQLDATABASE=$MYSQLDATABASE"\n\
echo ""\n\
if [ -n "$MYSQLHOST" ] && [ -n "$MYSQLUSER" ] && [ -n "$MYSQLDATABASE" ]; then\n\
  echo "Waiting for MySQL to be ready..."\n\
  sleep 5\n\
  echo "Setting up database..."\n\
  mysql -h $MYSQLHOST -u $MYSQLUSER -p$MYSQLPASSWORD $MYSQLDATABASE --ssl-mode=DISABLED < schema.sql && echo "Database initialized successfully!" || echo "Warning: Database setup failed or already initialized"\n\
else\n\
  echo "ERROR: Missing required MySQL environment variables"\n\
  exit 1\n\
fi\n\
echo "Starting PHP server on 0.0.0.0:8080..."\n\
php -S 0.0.0.0:8080 -t public' > /app/start.sh && chmod +x /app/start.sh

# Run startup script
CMD ["/app/start.sh"]
