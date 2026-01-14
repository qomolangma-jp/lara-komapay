FROM php:8.2-apache

# 必要な拡張機能のインストール
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        gd \
        mysqli \
        pdo \
        pdo_mysql \
        mbstring \
        xml

# Composerのインストール
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Apache mod_rewriteを有効化
RUN a2enmod rewrite

# ワークディレクトリの設定
WORKDIR /var/www/html

# Laravelの公開ディレクトリをドキュメントルートに設定
RUN echo '<Directory /var/www/html/public>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/laravel.conf && \
a2enconf laravel

RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-enabled/000-default.conf

# ファイルのコピー
COPY . .

# パーミッション設定
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
