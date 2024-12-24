FROM php:8.0-fpm

# PHPの設定ファイルをコピー（php.iniを設定）
COPY php.ini /usr/local/etc/php/

# 必要なパッケージのインストール
RUN apt update \
    && apt install -y default-mysql-client zlib1g-dev libzip-dev unzip \
    && docker-php-ext-install pdo_mysql zip

# Composerのインストール
RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer

# デフォルト作業ディレクトリを設定
WORKDIR /var/www