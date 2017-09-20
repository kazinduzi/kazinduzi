#FROM php:7.1-fpm
FROM php:7.2-rc-fpm

MAINTAINER Emmanuel Ndayiragije

# Update
RUN apt-get update --fix-missing && apt-get -y dist-upgrade

# Install PHP extensions
RUN apt-get install --no-install-recommends -y \
	git \
    unzip \
    #libmcrypt-dev libicu52 libmcrypt4 \
    #libpng12-dev libpq-dev file re2c libicu-dev zlib1g-dev \
    gifsicle libjpeg-progs optipng \
    libmagickcore-dev libmagickwand-dev libmagick++-dev libjpeg-dev \
    imagemagick xfonts-base xfonts-75dpi libfreetype6-dev \
	&& rm -rf /var/lib/apt/lists/*

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer --version

#Install Redis
ENV PHPREDIS_VERSION 3.1.3

RUN mkdir -p /usr/src/php/ext/redis \
    && curl -L https://github.com/phpredis/phpredis/archive/$PHPREDIS_VERSION.tar.gz | tar xvz -C /usr/src/php/ext/redis --strip 1 \
    && echo 'redis' >> /usr/src/php-available-exts \
    && docker-php-ext-install redis

# Set timezone
RUN rm /etc/localtime
RUN ln -s /usr/share/zoneinfo/Europe/Paris /etc/localtime
RUN "date"

RUN docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-png-dir=/usr/lib --with-jpeg-dir=/usr/lib \
	&& docker-php-ext-install gd

# Type docker-php-ext-install to see available extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

RUN echo 'alias sf="php app/console"' >> ~/.bashrc

WORKDIR /var/www/html

RUN apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*