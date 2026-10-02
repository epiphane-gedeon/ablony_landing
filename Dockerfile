FROM php:8.2-apache

# pdo_sqlite : utilisé par submit_waitlist.php / get_waitlist.php
# msmtp : sert de relais SMTP pour que mail() de PHP envoie vraiment les mails
#         (sinon mail() échoue silencieusement, il n'y a pas de MTA local dans le conteneur)
RUN apt-get update \
    && apt-get install -y --no-install-recommends libsqlite3-dev msmtp msmtp-mta \
    && docker-php-ext-install pdo pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

# PHP utilisera msmtp comme "sendmail" pour la fonction mail()
RUN echo "sendmail_path = /usr/bin/msmtp -C /etc/msmtprc -t" > /usr/local/etc/php/conf.d/mail.ini

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
