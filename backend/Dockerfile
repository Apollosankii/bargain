FROM yiisoftware/yii2-php:8.4-apache

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

COPY . .

RUN sed -i -e 's|/app/web|/app/backend/web|g' /etc/apache2/sites-available/000-default.conf \
    && chmod +x /app/scripts/render-entrypoint.sh

ENTRYPOINT ["/app/scripts/render-entrypoint.sh"]
