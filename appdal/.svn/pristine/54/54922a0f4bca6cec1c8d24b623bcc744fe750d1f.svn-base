FROM php:7.1-cli
WORKDIR /var/wwwroot
COPY . .
RUN docker-php-ext-install mysqli
CMD [ "php","index.php"]