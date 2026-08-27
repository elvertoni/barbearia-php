FROM php:8.2-apache

# Instalar pacotes de sistema necessários (git, unzip, libzip-dev)
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install pdo pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

# Habilitar mod_rewrite para o front controller
RUN a2enmod rewrite

# Copiar configuração do Apache
COPY docker/php/apache.conf /etc/apache2/sites-available/000-default.conf

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Definir diretório de trabalho
WORKDIR /var/www/html

# Instalar dependências do Composer (se composer.lock existir)
# Feito no entrypoint para funcionar com volume mount
RUN echo '#!/bin/bash\n\
if [ -f composer.json ] && [ ! -d vendor ]; then\n\
    composer install --no-interaction --prefer-dist\n\
fi\n\
apache2-foreground' > /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
