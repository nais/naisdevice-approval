FROM php:8.3-cli-alpine AS build
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
WORKDIR /app
COPY composer.json composer.lock src templates ./
RUN composer install -o --no-dev

FROM php:8.3-apache
RUN a2enmod rewrite
RUN echo "export ISSUER_ENTITY_ID" >> /etc/apache2/envvars && \
    echo "export LOGIN_URL" >> /etc/apache2/envvars && \
    echo "export ACCESS_GROUP" >> /etc/apache2/envvars && \
    echo "export AAD_CLIENT_ID" >> /etc/apache2/envvars && \
    echo "export SAML_CERT" >> /etc/apache2/envvars && \
    echo "export AAD_CLIENT_SECRET" >> /etc/apache2/envvars && \
    echo "export DOMAIN" >> /etc/apache2/envvars
COPY --from=build /app/vendor/ /var/www/vendor/
COPY templates/ /var/www/templates/
COPY src/ /var/www/src/
COPY public/ /var/www/html/

RUN sed -i "s/80/8080/g" /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

EXPOSE 8080
