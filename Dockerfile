FROM xigen/php:cli-composer as composer

COPY composer* /app/
COPY config/ /app/config
COPY src/ /app/src

RUN composer install -vvv -o -a --no-scripts --ignore-platform-reqs

FROM xigen/php:cli-73

ENV APP_ENV dev

WORKDIR /app

RUN apk add --update --no-cache ca-certificates curl ffmpeg python gnupg py-pip \
  && pip install -U youtube-dl 

COPY . /app
COPY --from=composer /app/vendor /app/vendor

RUN chmod +x /app/bin/console \
  && mkdir /app/var/tmp \
  && chmod 665 /app/var/tmp

ENTRYPOINT ["php", "bin/console", "server:run", "0.0.0.0:8000"]