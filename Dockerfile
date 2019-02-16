FROM xigen/php:cli-composer as composer

COPY composer* /app/
COPY config/ /app/config
COPY src/ /app/src

RUN composer install -vvv -o -a --no-scripts --ignore-platform-reqs

FROM xigen/php:fpm-73


ENV APP_ENV dev

RUN apk add --update --no-cache ca-certificates curl ffmpeg python gnupg py-pip \
  && pip install -U youtube-dl


COPY . /var/www
COPY --from=composer /app/vendor /var/www/vendor

RUN chmod +x /var/www/bin/console \
  && mkdir /var/www/var/tmp \
  && chmod 777 /var/www/var/tmp \
  && chown -Rf 82:82 /var/www

USER 82
