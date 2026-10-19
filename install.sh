#!/usr/bin/env bash
set -Eeuo pipefail
REPO="https://github.com/florianthepro/web-application-management-system";PORT=8443
while [ "$#" -gt 0 ];do case "$1" in --repository)REPO="$2";shift 2;;--port)PORT="$2";shift 2;;*)exit 2;;esac;done
[ "$(id -u)" -eq 0 ]||{ echo 'Bitte mit sudo ausführen.' >&2;exit 1; }
missing=();for c in git apache2 openssl rsync php;do command -v "$c" >/dev/null||missing+=("$c");done
php -m 2>/dev/null|grep -qi '^pdo_sqlite$'||missing+=(php-sqlite3)
if [ "${#missing[@]}" -gt 0 ];then apt-get update;apt-get install -y git apache2 libapache2-mod-php php-cli php-sqlite3 openssl rsync;fi
while ss -ltnH|awk '{print $4}'|grep -Eq "(:|])$PORT$"||grep -RhsE '^[[:space:]]*Listen[[:space:]]+' /etc/apache2|awk '{print $2}'|sed 's/.*://'|grep -qx "$PORT";do PORT=$((PORT+1));done
TMP="$(mktemp -d /var/tmp/wams.XXXXXX)";trap 'rm -rf "$TMP"' EXIT
GIT_TERMINAL_PROMPT=0 git clone --depth 1 "$REPO" "$TMP/repo"
while IFS= read -r -d '' f;do php -l "$f" >/dev/null;done < <(find "$TMP/repo" -name '*.php' -print0)
ID="$(cat /proc/sys/kernel/random/uuid)";ROOT="/opt/wams-$ID";rsync -a "$TMP/repo/" "$ROOT/";mkdir -p "$ROOT/state/tls" "$ROOT/state/instances"
chown -R root:www-data "$ROOT";chmod -R o-rwx "$ROOT";chown -R www-data:www-data "$ROOT/state";chmod -R ug+rwX "$ROOT/state"
IP="$(hostname -I|awk '{print $1}')";HOST="$(hostname -f 2>/dev/null||hostname)";openssl req -x509 -newkey rsa:2048 -nodes -sha256 -days 365 -subj "/CN=$HOST" -addext "subjectAltName=DNS:$HOST,IP:$IP" -keyout "$ROOT/state/tls/server.key" -out "$ROOT/state/tls/server.crt" >/dev/null 2>&1
SLUG="wams-${ID%%-*}";sed -e "s|__PORT__|$PORT|g" -e "s|__ROOT__|$ROOT|g" "$ROOT/resources/apache.conf.template" > "/etc/apache2/sites-available/$SLUG.conf";a2enmod ssl rewrite >/dev/null;a2ensite "$SLUG" >/dev/null;apache2ctl configtest;systemctl reload apache2
echo "Setup: https://$IP:$PORT/setup.php"
