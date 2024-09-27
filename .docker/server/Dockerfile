FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive

# Atualizar pacotes e instalar ferramentas básicas e Google Chrome
RUN apt update -y && \
    apt upgrade -y && \
    apt install -y \
      nano \
      vim \
      alien \
      libaio1 \
      wget \
      cron \
      telnet \
      openvpn \
      brotli \
      apache2 \
      curl \
      iputils-ping \
      libmagickwand-dev \
      imagemagick \
      git \
      lsof \
      xvfb \
      gnupg \
      software-properties-common \
      bash-completion \
      && rm -rf /var/lib/apt/lists/*

# Adicionar repositório do Google Chrome e instalar o Chrome
RUN wget -q -O - https://dl.google.com/linux/linux_signing_key.pub | apt-key add - && \
    sh -c 'echo "deb [arch=amd64] http://dl.google.com/linux/chrome/deb/ stable main" > /etc/apt/sources.list.d/google-chrome.list' && \
    apt update -y && \
    apt install -y google-chrome-stable && \
    rm -rf /var/lib/apt/lists/*

# Instalar PHP e extensões
RUN add-apt-repository -y ppa:ondrej/php && \
    apt update -y && \
    apt install -y \
      php8.3 \
      php8.3-common \
      php8.3-dev \
      libapache2-mod-php8.3 \
      php8.3-sqlite3 \
      php8.3-pgsql \
      php8.3-mysql \
      php8.3-sybase \
      php8.3-redis \
      php8.3-mongodb \
      php8.3-gd \
      php8.3-mbstring \
      php8.3-curl \
      php8.3-soap \
      php8.3-zip \
      php8.3-fpm \
      php8.3-bcmath \
      php8.3-xml \
      php8.3-intl \
      php8.3-ldap \
      php8.3-xmlrpc \
      php8.3-mcrypt \
      php8.3-odbc \
      php8.3-pdo-dblib \
      libapache2-mod-log-sql-mysql \
      unzip \
      gcc \
      g++ \
      libpq-dev \
      libc-dev \
      musl-dev \
      unixodbc-dev \
      make \
      autoconf \
      pkg-config \
      && rm -rf /var/lib/apt/lists/*

# Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php && \
    chmod +x composer.phar && \
    mv composer.phar /usr/local/bin/composer

# Instalar npm
RUN curl -sL https://deb.nodesource.com/setup_20.x | bash - && \
    apt install -y nodejs

# Instalar e configurar Imagick
RUN git clone https://github.com/Imagick/imagick.git && \
    cd imagick && \
    phpize && \
    ./configure && \
    make && \
    make install && \
    cd .. && \
    rm -rf imagick

# Configuração do Apache
RUN a2enmod rewrite && \
    phpenmod mbstring && \
    chmod -R 775 /var/www/html/ && \
    echo "ServerName dev-wuapi" >> /etc/apache2/apache2.conf

# Adicionar arquivos de configuração
ADD ./config/php-apache.ini /etc/php/8.3/apache2/php.ini
ADD ./config/php.ini /etc/php/8.3/cli/php.ini
ADD ./apache/000-default.conf /etc/apache2/sites-available/docker.wuapi.loc.conf
RUN a2ensite docker.wuapi.loc.conf && \
    a2dissite 000-default.conf

# Configuração do usuário
ARG WWWUSER
ARG WWWGROUP
RUN useradd -G www-data,root -u $WWWGROUP -d /home/$WWWUSER $WWWUSER && \
    mkdir -p /home/$WWWUSER/.composer && \
    chown -R $WWWUSER:root /home/$WWWUSER && \
    usermod -aG sudo $WWWUSER

# Configuração do cron
COPY --chown=www-data:www-data --chmod=644 cron/scheduler /etc/cron.d/scheduler

# Criar a pasta /storage e definir permissões
RUN mkdir -p /storage && chmod 777 /storage
RUN mkdir -p /.local && chmod 777 /.local

# Copiar o script de inicialização e configurar permissões
COPY start-container /usr/local/bin/start-container
RUN chmod +x /usr/local/bin/start-container

## Scheduler no cron
RUN crontab /etc/cron.d/scheduler

# Criar pasta para cache do fontconfig
RUN mkdir -p /var/www/.cache/fontconfig && chmod -R 777 /var/www/.cache/fontconfig
RUN mkdir -p /var/www/.cache/dconf && chmod -R 777 /var/www/.cache/dconf

# Expor a porta
EXPOSE 80

# Definir o ponto de entrada
ENTRYPOINT ["start-container"]
