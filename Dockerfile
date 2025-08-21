FROM php:8.2-fpm-bookworm

ARG WORKDIR=/var/www
ENV DOCUMENT_ROOT=${WORKDIR}
ENV CLIENT_MAX_BODY_SIZE=15M
ARG GUID=1000
ARG PUID=1000
ENV USER_NAME=www-data
ARG GROUP_NAME=www-data
ENV TIMEZONE="Europe/Rome"
ENV JP_DATA_PATH=/data

# Install system dependencies
RUN apt-get clean && apt-get update -y && apt-get install -y \
    wget \
    git \
    build-essential \
    apt-transport-https \
    software-properties-common \
    gnupg \
    curl \
    libfreetype6-dev libjpeg62-turbo-dev libmemcached-dev \
    libzip-dev libpng-dev libonig-dev libxml2-dev librdkafka-dev libpq-dev \
    python3 python3-pip python3-venv \
    nodejs npm \
    openssh-server \
    zip unzip \
    supervisor \
    sqlite3  \
    nano \
    cron \
    nginx

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions zip, mbstring, exif, bcmath, intl
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install zip mbstring exif pcntl bcmath -j$(nproc) gd intl pdo_mysql pdo_pgsql opcache

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
ENV COMPOSER_ALLOW_SUPERUSER=1

# Make Python environment
RUN python3 -m venv /var/python/venv

# Install Jellyfin
RUN curl -fsSL https://repo.jellyfin.org/debian/jellyfin_team.gpg.key | gpg --dearmor -o /usr/share/keyrings/jellyfin.gpg && \
    echo "deb [signed-by=/usr/share/keyrings/jellyfin.gpg] https://repo.jellyfin.org/debian $(lsb_release -cs) main" > /etc/apt/sources.list.d/jellyfin.list && \
    apt-get update && apt-get install -y jellyfin

# Install Torrent Stream Server
RUN npm install -g torrent-stream-server

#Install MediaFlowProxy
RUN /var/python/venv/bin/pip install mediaflow-proxy

# Set working directory
WORKDIR $WORKDIR

# Copy the Laravel application and necessary files
COPY www $WORKDIR
COPY src /var/src

# Copy the PHP, Supervisor and Nginx configuration files
ADD src/php.ini $PHP_INI_DIR/conf.d/
ADD src/opcache.ini $PHP_INI_DIR/conf.d/
ADD src/supervisord.conf /etc/supervisor/supervisord.conf

COPY src/entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint.sh &&  \
    ln -s /usr/local/bin/entrypoint.sh /

RUN rm -rf /etc/nginx/conf.d/default.conf && \
    rm -rf /etc/nginx/sites-enabled/default && \
    rm -rf /etc/nginx/sites-available/default && \
    rm -rf /etc/nginx/nginx.conf

COPY src/nginx.conf /etc/nginx/nginx.conf
COPY src/default.conf /etc/nginx/conf.d/

# Create the necessary folders
RUN mkdir -p /var/log/supervisor && \
    mkdir -p /var/log/nginx && \
    mkdir -p /var/cache/nginx && \
    mkdir -p /var/log/jellyfin && \
    mkdir -p /var/lib/jellyfin && \
    mkdir -p /var/cache/jellyfin

# Create the www-data user
RUN usermod -u ${PUID} ${USER_NAME}
RUN groupmod -g ${PUID} ${GROUP_NAME}

# Change folder permissions
RUN chown -R ${USER_NAME}:${GROUP_NAME} /var/www && \
    chown -R ${USER_NAME}:${GROUP_NAME} /var/log/ && \
    chown -R ${USER_NAME}:${GROUP_NAME} /var/python/ && \
    chown -R ${USER_NAME}:${GROUP_NAME} /etc/supervisor/conf.d/ && \
    chown -R ${USER_NAME}:${GROUP_NAME} $PHP_INI_DIR/conf.d/ && \
    touch /var/run/nginx.pid && \
    touch /var/run/jellyfin.pid && \
    chown -R ${USER_NAME}:${GROUP_NAME} /var/cache/nginx && \
    chown -R ${USER_NAME}:${GROUP_NAME} /var/lib/nginx/ && \
    chown -R ${USER_NAME}:${GROUP_NAME} /var/cache/jellyfin/ && \
    chown -R ${USER_NAME}:${GROUP_NAME} /var/lib/jellyfin/ && \
    chown -R ${USER_NAME}:${GROUP_NAME} /var/run/nginx.pid && \
    chown -R ${USER_NAME}:${GROUP_NAME} /var/run/jellyfin.pid && \
    chown -R ${USER_NAME}:${GROUP_NAME} /var/log/supervisor && \
    chown -R ${USER_NAME}:${GROUP_NAME} /etc/nginx/nginx.conf && \
    chown -R ${USER_NAME}:${GROUP_NAME} /etc/nginx/conf.d/ && \
    chown -R ${USER_NAME}:${GROUP_NAME} /tmp


# End of process
#USER ${USER_NAME}
EXPOSE 8096
ENTRYPOINT ["entrypoint.sh"]