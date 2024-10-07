
# chmod +x conf_server.sh

# Atualizar pacotes e instalar ferramentas básicas e Google Chrome
apt update -y && \
apt upgrade -y && \
apt install -y \
  nano \
  libaio1 \
  wget \
  cron \
  apache2 \
  curl \
  git \
  lsof \
  xvfb \
  gnupg \
  software-properties-common \
  bash-completion

# Adicionar repositório do Google Chrome e instalar o Chrome
wget -q -O - https://dl.google.com/linux/linux_signing_key.pub | apt-key add - && \
sh -c 'echo "deb [arch=amd64] http://dl.google.com/linux/chrome/deb/ stable main" > /etc/apt/sources.list.d/google-chrome.list' && \
apt update -y && \
apt install -y google-chrome-stable

# Instalar PHP e extensões
add-apt-repository -y ppa:ondrej/php && \
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
  pkg-config

# Instalar Composer
curl -sS https://getcomposer.org/installer | php && \
chmod +x composer.phar && \
mv composer.phar /usr/local/bin/composer

# Instalar npm
curl -sL https://deb.nodesource.com/setup_20.x | bash - && \
apt install -y nodejs

# Configuração do cron
cp cron/scheduler /etc/cron.d/scheduler && \
chmod 644 /etc/cron.d/scheduler && \
crontab /etc/cron.d/scheduler

# Criar a pasta /storage e definir permissões
mkdir -p /storage && chmod 777 /storage
mkdir -p /.local && chmod 777 /.local

# Copiar o script de inicialização e configurar permissões
cp start-container /usr/local/bin/start-container && \
chmod +x /usr/local/bin/start-container

# Criar pasta para cache do fontconfig
mkdir -p /var/www/.cache/fontconfig && chmod -R 777 /var/www/.cache/fontconfig
mkdir -p /var/www/.cache/dconf && chmod -R 777 /var/www/.cache/dconf

echo "Configuração completa!"
