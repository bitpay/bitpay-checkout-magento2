FROM php:8.4

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN apt-get update && apt-get -y install \
    mariadb-client \
    apt-transport-https \
    git \
    zlib1g-dev \
    zip \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libsodium-dev \
    libicu-dev \
    libxml2-dev \
    libxslt-dev \
    xmlstarlet \
    netcat-openbsd \
    && apt-get -y autoremove \
    && apt-get clean autoclean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN docker-php-ext-install pdo pdo_mysql
RUN docker-php-ext-install xsl
RUN docker-php-ext-install soap
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install -j$(nproc) gd
RUN docker-php-ext-install bcmath
RUN docker-php-ext-install sodium
RUN docker-php-ext-install zip
RUN docker-php-ext-install intl
RUN docker-php-ext-install sockets
RUN docker-php-ext-install ftp
RUN docker-php-ext-enable ftp

ENV MODULE_NAME=$MODULE_NAME
ENV MODULE_SOURCE=$MODULE_SOURCE
ENV COMPOSER_NAME=$COMPOSER_NAME
ENV MAGENTO_VERSION=$MAGENTO_VERSION
ENV PROJECT_NAME=$PROJECT_NAME
ENV PHPUNIT_FILE=$PHPUNIT_FILE
ENV GITHUB_WORKSPACE=$GITHUB_WORKSPACE
ENV MAGENTO_MARKETPLACE_USERNAME=$MAGENTO_MARKETPLACE_USERNAME
ENV MAGENTO_MARKETPLACE_PASSWORD=$MAGENTO_MARKETPLACE_PASSWORD

COPY .github/2.4.8/php-8.4/entrypoint.sh /entrypoint.sh
RUN chmod 755 /entrypoint.sh

# COPY . $GITHUB_WORKSPACE
COPY . /home/runner/work/bitpay-checkout-magento2/bitpay-checkout-magento2

ENTRYPOINT ["bash", "/entrypoint.sh"]
