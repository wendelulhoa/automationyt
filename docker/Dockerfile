FROM php:8.2.16-fpm

## Define o fuso horário para America/Sao_Paulo
ENV TZ=America/Sao_Paulo \
    DEBIAN_FRONTEND=noninteractive

## Pacotes essenciais
RUN apt update -y && apt install -y --no-install-recommends \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zlib1g-dev \
    libzip-dev \
    libbrotli-dev \
    zip \
    unzip \
    supervisor \
    cron \
    nginx \
    ffmpeg \
    procps && \
    apt clean && rm -rf /var/lib/apt/lists/*

# Instalar npm
RUN curl -sL https://deb.nodesource.com/setup_20.x | bash - && \
    apt install -y nodejs

## Extensões PHP
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath sockets zip intl gd

## Instala Redis via PECL
RUN pecl install redis && docker-php-ext-enable redis && rm -rf /tmp/pear

## Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

## Usuário
ARG WWWGROUP=1337
RUN groupadd --force -g $WWWGROUP sail && \
    useradd -ms /bin/bash --no-user-group -g $WWWGROUP -u 1337 sail

## Copia arquivos de configuração
COPY --chown=sail:sail supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY --chown=sail:sail --chmod=775 start-container /usr/local/bin/start-container
COPY --chown=sail:sail --chmod=644 cron/scheduler /etc/cron.d/scheduler

## Configura NGINX
RUN rm -rf /etc/nginx/sites-enabled/* && rm -rf /etc/nginx/sites-available/*
COPY nginx/site.conf /etc/nginx/sites-enabled/site.conf

## Scheduler no cron
RUN crontab /etc/cron.d/scheduler

## Limpeza
RUN apt autoremove -y && apt clean

ENTRYPOINT ["start-container"]