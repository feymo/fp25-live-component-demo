FROM dunglas/frankenphp

RUN install-php-extensions \
    apcu \
    intl \
    zip \
    opcache \
    pdo_mysql \
    pdo_pgsql \
    xsl \
    gd \
    mbstring \
    http \
    @composer

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PATH="${PATH}:/root/.composer/vendor/bin"

COPY ./devops/php/.bashrc /root/.bashrc
