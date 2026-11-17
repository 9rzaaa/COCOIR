FROM php:8.3-cli-alpine

WORKDIR /srv/courier

COPY index.php /srv/courier/index.php
RUN mkdir -p /srv/courier/data && chmod -R 777 /srv/courier/data

EXPOSE 8095

CMD ["php", "-S", "0.0.0.0:8095", "/srv/courier/index.php"]
