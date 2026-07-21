#!/bin/sh

set -eu

target="${1:-.env.production}"
image="${HTSMS_BOOTSTRAP_IMAGE:-htsms:local}"
http_port="${HTSMS_BOOTSTRAP_HTTP_PORT:-8085}"

if [ -s "$target" ]; then
    echo "Refusing to overwrite non-empty $target" >&2
    exit 1
fi

umask 077
temporary="$(mktemp "${target}.XXXXXX")"
trap 'rm -f "$temporary"' EXIT HUP INT TERM

app_key="$(openssl rand -base64 32 | tr -d '\n')"
database_password="$(openssl rand -hex 32)"
redis_password="$(openssl rand -hex 32)"

sed \
    -e "s|^HTSMS_IMAGE=.*|HTSMS_IMAGE=$image|" \
    -e "s|^HTSMS_ENV_FILE=.*|HTSMS_ENV_FILE=.env.production|" \
    -e "s|^APP_KEY=.*|APP_KEY=base64:$app_key|" \
    -e "s|^DB_PASSWORD=.*|DB_PASSWORD=$database_password|" \
    -e "s|^REDIS_PASSWORD=.*|REDIS_PASSWORD=$redis_password|" \
    -e "s|^MAIL_MAILER=.*|MAIL_MAILER=log|" \
    .env.production.example > "$temporary"

printf '%s\n' "HTSMS_HTTP_PORT=$http_port" >> "$temporary"
chmod 600 "$temporary"
mv -f "$temporary" "$target"
trap - EXIT HUP INT TERM

echo "Created protected production environment at $target"
