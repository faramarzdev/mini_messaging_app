FROM php:8.4-cli

# System-level libraries needed to compile the PHP extensions below.
# php:8.4-cli is a minimal image — it doesn't include dev headers for
# Postgres, image processing, or internationalization by default.
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libicu-dev \
    autoconf \
    gcc \
    make \
    && rm -rf /var/lib/apt/lists/*

# Configure gd (needs freetype/jpeg support flags) then install the
# PHP extensions Chat actually depends on:
#   pdo + pdo_pgsql -> Eloquent/Postgres
#   zip             -> Composer needs it to unpack packages
#   gd              -> Intervention Image (profile pictures)
#   intl            -> Laravel's underlying string/locale handling
#   pcntl           -> SIGINT, SIGTERM, SIGTSTP are needed for reverb
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql pgsql zip gd intl pcntl

# phpredis isn't bundled with PHP core — install via PECL.
# Your config/database.php defaults REDIS_CLIENT to 'phpredis', so this
# extension is what actually makes the Redis facade work.
RUN pecl install redis && docker-php-ext-enable redis

# The php image has no Composer. Rather than installing it via a curl
# script, copy the official binary straight out of Composer's own image —
# this is the standard, recommended way to get Composer into a PHP image.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

EXPOSE 8000
