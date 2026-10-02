#!/bin/sh
set -e

# Génère /etc/msmtprc à partir des variables d'env (issues du .env via docker-compose)
# pour que mail() de PHP relaie réellement vers le SMTP de LWS.
if [ -n "$SMTP_HOST" ]; then
  if [ "$SMTP_PORT" = "465" ]; then
    TLS_STARTTLS="off"
  else
    TLS_STARTTLS="on"
  fi

  cat > /etc/msmtprc <<EOF
defaults
auth on
tls on
tls_starttls ${TLS_STARTTLS}
logfile /var/log/msmtp.log

account default
host ${SMTP_HOST}
port ${SMTP_PORT:-587}
from ${SMTP_FROM:-$SMTP_USER}
user ${SMTP_USER}
password ${SMTP_PASS}
EOF

  # msmtp refuse de démarrer si le fichier de conf est lisible par d'autres utilisateurs
  chown www-data:www-data /etc/msmtprc
  chmod 600 /etc/msmtprc
else
  echo "⚠️  SMTP_HOST absent de l'environnement — mail() ne pourra pas envoyer de mail."
fi

# Le dossier data/ (base SQLite) doit être accessible en écriture par www-data
mkdir -p /var/www/html/data
chown -R www-data:www-data /var/www/html/data

exec docker-php-entrypoint "$@"
