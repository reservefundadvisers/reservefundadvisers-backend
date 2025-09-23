FROM php:8.2-cli

ARG USER_ID
ARG GROUP_ID

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    sqlite3 \
    libsqlite3-dev

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql pdo_sqlite

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Create user and group with provided IDs
RUN groupadd -g ${GROUP_ID} appgroup || true \
    && useradd -u ${USER_ID} -g ${GROUP_ID} -m appuser || true

# Set alias for listing files
RUN echo "alias ll='ls -la'" >> /home/appuser/.bashrc

# Install dependencies
RUN chown ${USER_ID}:${GROUP_ID} /usr/local/bin/composer

# Set working directory
WORKDIR /app

# Ensure correct ownership regardless of user/group ID
RUN chown -R ${USER_ID}:${GROUP_ID} /app

# Switch to appuser for any subsequent commands
USER ${USER_ID}:${GROUP_ID}

# Install dependencies
#RUN composer install

# Command to keep the container running
CMD ["tail", "-f", "/dev/null"]